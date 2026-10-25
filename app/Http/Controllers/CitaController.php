<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\Especialidad;
use App\Models\EstadoCita;
use App\Models\Ficha;
use App\Models\HistorialEstadoCita;
use App\Models\HorarioAtencion;
use App\Models\Medico;
use App\Models\Paciente;
use App\Services\BitacoraService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Controlador del Núcleo de Citas Médicas (Módulo P4 - PUDS / RF06 - RF12 / CU14 - CU17)
 * Gestiona el ciclo de vida completo de las citas en estricto orden CRUD.
 */
class CitaController extends Controller
{
    // =========================================================================
    // 1. CREATE (Programación y Reserva de Citas Médicas - RF06 / CU15)
    // =========================================================================

    /**
     * [CREATE - Vista]: Muestra el formulario para agendar una nueva cita
     */
    public function create(Request $request)
    {
        $pacientes = Paciente::with('persona')->where('estado', true)->orderBy('id', 'desc')->get();
        $especialidades = Especialidad::where('estado', true)->orderBy('nombre')->get();
        $medicos = Medico::with(['persona', 'especialidades'])->where('estado', true)->get();

        // Parámetros opcionales precargados desde otras vistas
        $pacientePre = $request->input('paciente_id');
        $medicoPre = $request->input('medico_id');
        $especialidadPre = $request->input('especialidad_id');

        return view('citas.create', compact('pacientes', 'especialidades', 'medicos', 'pacientePre', 'medicoPre', 'especialidadPre'));
    }

    /**
     * [CREATE - Guardar]: Registra la cita con validación anti-solapamiento estricta (RN07, RN13)
     */
    public function store(Request $request)
    {
        // 1. Validaciones de entrada
        $request->validate([
            'paciente_id' => 'required|exists:pacientes,id',
            'medico_id' => 'required|exists:medicos,id',
            'especialidad_id' => 'required|exists:especialidades,id',
            'fecha' => 'required|date|after_or_equal:today',
            'ficha_id' => 'required|exists:fichas,id',
            'modalidad' => 'required|in:Presencial,Virtual',
            'motivo' => 'nullable|string|max:500',
        ], [
            'fecha.after_or_equal' => 'No se pueden agendar citas en fechas pasadas (RN06).',
            'ficha_id.required' => 'Debe seleccionar un turno/ficha de atención disponible.',
        ]);

        $medicoId = $request->input('medico_id');
        $pacienteId = $request->input('paciente_id');
        $fecha = $request->input('fecha');
        $fichaId = $request->input('ficha_id');

        // 2. Obtener datos de la Ficha seleccionada
        $ficha = Ficha::findOrFail($fichaId);

        // 3. Verificación Anti-Solapamiento en Servidor (RN07, RN13):
        // Impedir que dos citas activas (no canceladas) ocupen la misma ficha y fecha para el médico
        $estadoCancelada = EstadoCita::where('nombre', 'Cancelada')->first();
        $idCancelada = $estadoCancelada ? $estadoCancelada->id : 4;

        $solapada = Cita::where('medico_id', $medicoId)
            ->where('fecha', $fecha)
            ->where('ficha_id', $fichaId)
            ->where('estado_id', '!=', $idCancelada)
            ->exists();

        if ($solapada) {
            return back()->withInput()->withErrors([
                'ficha_id' => 'Conflicto de horario: El turno seleccionado acaba de ser reservado por otro usuario. Por favor seleccione otro turno (RN07).',
            ]);
        }

        // 4. Obtener Estado inicial "Programada"
        $estadoProgramada = EstadoCita::firstOrCreate(
            ['nombre' => 'Programada'],
            ['descripcion' => 'Cita reservada pendiente de confirmación']
        );

        // 5. Transacción atómica de guardado
        $cita = DB::transaction(function () use ($request, $ficha, $estadoProgramada, $fecha) {
            // A. Autogenerar código único correlativo (ej. CM-0001)
            $ultimoId = Cita::max('id') ?? 0;
            $codigo = 'CM-' . str_pad($ultimoId + 1, 4, '0', STR_PAD_LEFT);

            // B. Crear registro central de Cita
            $nuevaCita = Cita::create([
                'codigo' => $codigo,
                'paciente_id' => $request->input('paciente_id'),
                'medico_id' => $request->input('medico_id'),
                'especialidad_id' => $request->input('especialidad_id'),
                'ficha_id' => $ficha->id,
                'estado_id' => $estadoProgramada->id,
                'usuario_registro_id' => Auth::id(),
                'fecha' => $fecha,
                'hora_inicio' => $ficha->hora_inicio,
                'hora_fin' => $ficha->hora_fin,
                'motivo' => trim($request->input('motivo')),
                'modalidad' => $request->input('modalidad'),
                'fecha_creacion' => now(),
            ]);

            // C. Registro inicial inmutable en historial_estados_cita
            HistorialEstadoCita::create([
                'cita_id' => $nuevaCita->id,
                'estado_anterior_id' => null,
                'estado_nuevo_id' => $estadoProgramada->id,
                'usuario_id' => Auth::id(),
                'fecha_hora' => now(),
                'motivo' => 'Registro y programación inicial de la cita médica.',
            ]);

            // D. Auditoría en Bitácora
            BitacoraService::registrar(
                accion: 'PROGRAMAR_CITA',
                tabla: 'citas',
                registroId: $nuevaCita->id,
                detalles: "Cita programada {$codigo} para {$nuevaCita->paciente->nombre_completo} con Dr(a). {$nuevaCita->medico->nombre_completo} el {$fecha} ({$ficha->hora_inicio}-{$ficha->hora_fin})"
            );

            return $nuevaCita;
        });

        return redirect()->route('citas.show', $cita)->with('success', "Cita médica {$cita->codigo} agendada exitosamente.");
    }

    // =========================================================================
    // 2. READ (Agenda Médica Diaria, Filtros y Consulta de Citas - RF11 / CU17)
    // =========================================================================

    /**
     * [READ - Agenda y Listado]: Muestra la agenda de citas con filtros combinados
     */
    public function index(Request $request)
    {
        $fecha = $request->input('fecha', today()->toDateString());
        $medicoId = $request->input('medico_id');
        $especialidadId = $request->input('especialidad_id');
        $estadoId = $request->input('estado_id');
        $buscar = trim($request->input('buscar'));

        $query = Cita::with(['paciente.persona', 'medico.persona', 'especialidad', 'estadoCita', 'ficha']);

        // Si el usuario autenticado es Médico, filtrar solo sus citas
        if (Auth::user()->rol->nombre === 'Medico') {
            $medicoPerfil = Medico::where('persona_id', Auth::user()->persona_id)->first();
            if ($medicoPerfil) {
                $query->where('medico_id', $medicoPerfil->id);
            }
        } elseif (!empty($medicoId)) {
            $query->where('medico_id', $medicoId);
        }

        // Filtro por fecha si se especifica
        if (!empty($fecha)) {
            $query->whereDate('fecha', $fecha);
        }

        if (!empty($especialidadId)) {
            $query->where('especialidad_id', $especialidadId);
        }

        if (!empty($estadoId)) {
            $query->where('estado_id', $estadoId);
        }

        if (!empty($buscar)) {
            $query->where(function ($q) use ($buscar) {
                $q->where('codigo', 'ilike', "%{$buscar}%")
                  ->orWhereHas('paciente.persona', function ($qp) use ($buscar) {
                      $qp->where('ci', 'ilike', "%{$buscar}%")
                         ->orWhere('nombres', 'ilike', "%{$buscar}%")
                         ->orWhere('apellidos', 'ilike', "%{$buscar}%");
                  });
            });
        }

        $citas = $query->orderBy('fecha', 'desc')
                       ->orderBy('hora_inicio', 'asc')
                       ->paginate(15)
                       ->withQueryString();

        $medicos = Medico::with('persona')->where('estado', true)->get();
        $especialidades = Especialidad::where('estado', true)->orderBy('nombre')->get();
        $estados = EstadoCita::all();

        return view('citas.index', compact('citas', 'fecha', 'medicoId', 'especialidadId', 'estadoId', 'buscar', 'medicos', 'especialidades', 'estados'));
    }

    /**
     * [READ - Detalle y Trazabilidad]: Ficha completa de la cita con historial inmutable
     */
    public function show(Cita $cita)
    {
        $cita->load([
            'paciente.persona',
            'medico.persona',
            'especialidad',
            'estadoCita',
            'ficha',
            'usuarioRegistro',
            'historialEstados' => function ($q) {
                $q->with(['estadoAnterior', 'estadoNuevo', 'usuario'])->orderBy('fecha_hora', 'asc');
            }
        ]);

        $estadosDisponibles = EstadoCita::all();

        return view('citas.show', compact('cita', 'estadosDisponibles'));
    }

    // =========================================================================
    // 3. UPDATE (Reprogramación de Citas y Transiciones de Estado - RF08, RF10)
    // =========================================================================

    /**
     * [UPDATE - Vista]: Muestra el formulario de reprogramación de fecha/hora
     */
    public function edit(Cita $cita)
    {
        // Regla: Una cita atendida o cancelada no puede reprogramarse directamente (RF08)
        if (in_array($cita->estadoCita->nombre, ['Atendida', 'Cancelada'])) {
            return redirect()->route('citas.show', $cita)->with(
                'error',
                "La cita en estado {$cita->estadoCita->nombre} no puede ser reprogramada (RF08)."
            );
        }

        $cita->load(['medico.persona', 'paciente.persona', 'especialidad', 'ficha']);
        return view('citas.edit', compact('cita'));
    }

    /**
     * [UPDATE - Guardar Reprogramación]: Guarda la nueva fecha/turno y registra en historial
     */
    public function update(Request $request, Cita $cita)
    {
        if (in_array($cita->estadoCita->nombre, ['Atendida', 'Cancelada'])) {
            return redirect()->route('citas.show', $cita)->with('error', 'Cita no modificable.');
        }

        $request->validate([
            'fecha' => 'required|date|after_or_equal:today',
            'ficha_id' => 'required|exists:fichas,id',
            'motivo_reprogramacion' => 'required|string|max:255',
        ], [
            'fecha.after_or_equal' => 'La nueva fecha debe ser igual o posterior a hoy.',
            'motivo_reprogramacion.required' => 'Debe registrar el motivo de la reprogramación.',
        ]);

        $nuevaFecha = $request->input('fecha');
        $nuevaFichaId = $request->input('ficha_id');
        $nuevaFicha = Ficha::findOrFail($nuevaFichaId);

        // Validar no solapamiento
        $estadoCancelada = EstadoCita::where('nombre', 'Cancelada')->first();
        $idCancelada = $estadoCancelada ? $estadoCancelada->id : 4;

        $solapada = Cita::where('medico_id', $cita->medico_id)
            ->where('fecha', $nuevaFecha)
            ->where('ficha_id', $nuevaFichaId)
            ->where('id', '!=', $cita->id)
            ->where('estado_id', '!=', $idCancelada)
            ->exists();

        if ($solapada) {
            return back()->withInput()->withErrors([
                'ficha_id' => 'El turno seleccionado ya se encuentra ocupado en esa fecha.',
            ]);
        }

        DB::transaction(function () use ($cita, $nuevaFecha, $nuevaFicha, $request) {
            $fechaAnterior = $cita->fecha;
            $horaAnterior = $cita->hora_inicio;

            $cita->update([
                'fecha' => $nuevaFecha,
                'ficha_id' => $nuevaFicha->id,
                'hora_inicio' => $nuevaFicha->hora_inicio,
                'hora_fin' => $nuevaFicha->hora_fin,
            ]);

            // Historial inmutable
            HistorialEstadoCita::create([
                'cita_id' => $cita->id,
                'estado_anterior_id' => $cita->estado_id,
                'estado_nuevo_id' => $cita->estado_id,
                'usuario_id' => Auth::id(),
                'fecha_hora' => now(),
                'motivo' => "Reprogramación: cambió de {$fechaAnterior} {$horaAnterior} a {$nuevaFecha} {$nuevaFicha->hora_inicio}. Motivo: " . $request->input('motivo_reprogramacion'),
            ]);

            BitacoraService::registrar(
                accion: 'REPROGRAMAR_CITA',
                tabla: 'citas',
                registroId: $cita->id,
                detalles: "Cita {$cita->codigo} reprogramada a {$nuevaFecha} {$nuevaFicha->hora_inicio}"
            );
        });

        return redirect()->route('citas.show', $cita)->with('success', 'Cita médica reprogramada exitosamente.');
    }

    /**
     * [UPDATE - Transición de Estado]: Cambia a Confirmada, Atendida, Inasistencia (RF10)
     */
    public function cambiarEstado(Request $request, Cita $cita)
    {
        $request->validate([
            'nuevo_estado_id' => 'required|exists:estados_cita,id',
            'motivo' => 'nullable|string|max:255',
        ]);

        $nuevoEstado = EstadoCita::findOrFail($request->input('nuevo_estado_id'));
        $estadoAnteriorId = $cita->estado_id;

        DB::transaction(function () use ($cita, $nuevoEstado, $estadoAnteriorId, $request) {
            $cita->update(['estado_id' => $nuevoEstado->id]);

            HistorialEstadoCita::create([
                'cita_id' => $cita->id,
                'estado_anterior_id' => $estadoAnteriorId,
                'estado_nuevo_id' => $nuevoEstado->id,
                'usuario_id' => Auth::id(),
                'fecha_hora' => now(),
                'motivo' => $request->input('motivo') ?: "Transición de estado a {$nuevoEstado->nombre}.",
            ]);

            BitacoraService::registrar(
                accion: 'CAMBIO_ESTADO_CITA',
                tabla: 'citas',
                registroId: $cita->id,
                detalles: "Cita {$cita->codigo} cambió a estado {$nuevoEstado->nombre}"
            );
        });

        return back()->with('success', "Estado de la cita actualizado a {$nuevoEstado->nombre}.");
    }

    // =========================================================================
    // 4. DELETE (Cancelación Lógica con Motivo Obligatorio - RF09 / RN09 / RN11)
    // =========================================================================

    /**
     * [DELETE - Cancelación Lógica]: Cancela la cita sin eliminar el registro físico (RF09)
     */
    public function destroy(Request $request, Cita $cita)
    {
        $request->validate([
            'motivo_cancelacion' => 'required|string|min:5|max:255',
        ], [
            'motivo_cancelacion.required' => 'El motivo de cancelación es obligatorio según la norma (RF09).',
        ]);

        if ($cita->estadoCita && $cita->estadoCita->nombre === 'Atendida') {
            return back()->with('error', 'Una cita atendida no puede cancelarse (RF09).');
        }

        $estadoCancelada = EstadoCita::where('nombre', 'Cancelada')->firstOrFail();

        DB::transaction(function () use ($cita, $estadoCancelada, $request) {
            $estadoAnteriorId = $cita->estado_id;

            // Actualización lógica de la cita: se anota el motivo y se libera el horario (RN09)
            $cita->update([
                'estado_id' => $estadoCancelada->id,
                'motivo_cancelacion' => trim($request->input('motivo_cancelacion')),
            ]);

            // Registro inmutable en el historial
            HistorialEstadoCita::create([
                'cita_id' => $cita->id,
                'estado_anterior_id' => $estadoAnteriorId,
                'estado_nuevo_id' => $estadoCancelada->id,
                'usuario_id' => Auth::id(),
                'fecha_hora' => now(),
                'motivo' => 'Cancelación: ' . $request->input('motivo_cancelacion'),
            ]);

            BitacoraService::registrar(
                accion: 'CANCELAR_CITA',
                tabla: 'citas',
                registroId: $cita->id,
                detalles: "Cita {$cita->codigo} cancelada. Motivo: {$request->input('motivo_cancelacion')}"
            );
        });

        return redirect()->route('citas.show', $cita)->with('success', 'La cita ha sido cancelada. Su registro se conserva en el historial (RN11).');
    }

    // =========================================================================
    // Métodos Auxiliares / API JSON (CU14 - Consultar Disponibilidad)
    // =========================================================================

    /**
     * [API - CU14]: Consulta fichas disponibles de un médico en una fecha específica
     */
    public function consultarFichas(Request $request)
    {
        $medicoId = $request->input('medico_id');
        $fecha = $request->input('fecha');

        if (!$medicoId || !$fecha) {
            return response()->json(['fichas' => [], 'mensaje' => 'Parámetros incompletos.']);
        }

        // Determinar día de la semana en español
        $diasMapa = [
            1 => 'Lunes', 2 => 'Martes', 3 => 'Miercoles', 4 => 'Jueves',
            5 => 'Viernes', 6 => 'Sabado', 7 => 'Domingo'
        ];
        $diaSemanaNumero = Carbon::parse($fecha)->dayOfWeekIso;
        $diaSemana = $diasMapa[$diaSemanaNumero] ?? '';

        // Buscar jornada del médico para ese día
        $horario = HorarioAtencion::where('medico_id', $medicoId)
            ->where('dia_semana', $diaSemana)
            ->where('estado', true)
            ->first();

        if (!$horario) {
            return response()->json([
                'fichas' => [],
                'mensaje' => "El médico no tiene horario de atención configurado para los días {$diaSemana}.",
            ]);
        }

        // Obtener fichas ocupadas en esa fecha (citas no canceladas)
        $estadoCancelada = EstadoCita::where('nombre', 'Cancelada')->first();
        $idCancelada = $estadoCancelada ? $estadoCancelada->id : 4;

        $fichasOcupadas = Cita::where('medico_id', $medicoId)
            ->where('fecha', $fecha)
            ->where('estado_id', '!=', $idCancelada)
            ->pluck('ficha_id')
            ->toArray();

        // Mapear fichas del horario con estado de disponibilidad
        $fichasResultado = $horario->fichas()->orderBy('numero')->get()->map(function ($f) use ($fichasOcupadas) {
            return [
                'id' => $f->id,
                'numero' => $f->numero,
                'hora_inicio' => substr($f->hora_inicio, 0, 5),
                'hora_fin' => substr($f->hora_fin, 0, 5),
                'disponible' => !in_array($f->id, $fichasOcupadas),
            ];
        });

        return response()->json([
            'dia_semana' => $diaSemana,
            'horario_inicio' => substr($horario->hora_inicio, 0, 5),
            'horario_fin' => substr($horario->hora_fin, 0, 5),
            'fichas' => $fichasResultado,
        ]);
    }
}
