<?php

namespace App\Http\Controllers;

use App\Models\Ficha;
use App\Models\HorarioAtencion;
use App\Models\Medico;
use App\Services\BitacoraService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Controlador de Horarios de Atencion y Fichas (Modulo P3 - PUDS / RF05 / CU13)
 * Administra las jornadas de los medicos y calcula turnos/fichas de 30 min en orden CRUD.
 */
class HorarioAtencionController extends Controller
{
    // =========================================================================
    // 1. CREATE (Definir Horario y Generación Automática de Fichas)
    // =========================================================================

    /**
     * [CREATE - Vista]: Muestra el formulario para programar una jornada
     */
    public function create(Request $request)
    {
        $medicoSeleccionado = $request->input('medico_id');
        $medicos = Medico::with('persona')->where('estado', true)->get();
        $diasSemana = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo'];

        return view('horarios.create', compact('medicos', 'medicoSeleccionado', 'diasSemana'));
    }

    /**
     * [CREATE - Guardar]: Crea el horario y calcula automáticamente sus fichas (RF05)
     */
    public function store(Request $request)
    {
        $request->validate([
            'medico_id' => 'required|exists:medicos,id',
            'dia_semana' => 'required|in:Lunes,Martes,Miercoles,Jueves,Viernes,Sabado,Domingo',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
            'duracion_cita_min' => 'required|integer|min:10|max:120',
        ], [
            'hora_fin.after' => 'La hora final debe ser posterior a la hora inicial.',
            'duracion_cita_min.min' => 'La duración mínima de cada turno es de 10 minutos.',
        ]);

        $medicoId = $request->input('medico_id');
        $diaSemana = $request->input('dia_semana');
        $horaInicio = $request->input('hora_inicio');
        $horaFin = $request->input('hora_fin');
        $duracion = (int) $request->input('duracion_cita_min', 30);

        // Validación anti-solapamiento de horarios del mismo médico el mismo día
        $existeSolapamiento = HorarioAtencion::where('medico_id', $medicoId)
            ->where('dia_semana', $diaSemana)
            ->where('estado', true)
            ->where(function ($q) use ($horaInicio, $horaFin) {
                $q->whereBetween('hora_inicio', [$horaInicio, $horaFin])
                  ->orWhereBetween('hora_fin', [$horaInicio, $horaFin])
                  ->orWhere(function ($sub) use ($horaInicio, $horaFin) {
                      $sub->where('hora_inicio', '<=', $horaInicio)
                          ->where('hora_fin', '>=', $horaFin);
                  });
            })->exists();

        if ($existeSolapamiento) {
            return back()->withInput()->withErrors([
                'hora_inicio' => 'El médico ya tiene una jornada activa que se solapa con este rango horario el día ' . $diaSemana,
            ]);
        }

        DB::transaction(function () use ($medicoId, $diaSemana, $horaInicio, $horaFin, $duracion) {
            // A. Crear registro de Horario de Atencion
            $horario = HorarioAtencion::create([
                'medico_id' => $medicoId,
                'dia_semana' => $diaSemana,
                'hora_inicio' => $horaInicio,
                'hora_fin' => $horaFin,
                'duracion_cita_min' => $duracion,
                'estado' => true,
            ]);

            // B. Generar automaticamente las Fichas (bloques predeterminados de atencion)
            $this->generarFichas($horario);

            // C. Auditoría en Bitácora
            $medico = Medico::find($medicoId);
            BitacoraService::registrar(
                accion: 'CREAR_HORARIO_Y_FICHAS',
                tabla: 'horarios_atencion',
                registroId: $horario->id,
                detalles: "Horario creado para Dr(a). {$medico->nombre_completo} ({$diaSemana} {$horaInicio}-{$horaFin}, turnos de {$duracion} min)"
            );
        });

        return redirect()->route('horarios.index', ['medico_id' => $medicoId])
            ->with('success', 'Jornada de atención registrada y fichas de atención calculadas automáticamente.');
    }

    // =========================================================================
    // 2. READ (Lectura, Consultas y Fichas de Atención)
    // =========================================================================

    /**
     * [READ - Catálogo]: Lista horarios con filtro por médico y día
     */
    public function index(Request $request)
    {
        $medicoId = $request->input('medico_id');
        $diaSemana = $request->input('dia_semana');

        $query = HorarioAtencion::with(['medico.persona'])->withCount('fichas');

        if (!empty($medicoId)) {
            $query->where('medico_id', $medicoId);
        }

        if (!empty($diaSemana)) {
            $query->where('dia_semana', $diaSemana);
        }

        $horarios = $query->orderBy('medico_id')->orderBy('dia_semana')->paginate(10)->withQueryString();
        $medicos = Medico::with('persona')->where('estado', true)->get();

        return view('horarios.index', compact('horarios', 'medicos', 'medicoId', 'diaSemana'));
    }

    /**
     * [READ - Detalle y Fichas]: Muestra los turnos calculados del horario
     */
    public function show(HorarioAtencion $horario)
    {
        $horario->load(['medico.persona', 'fichas' => function ($q) {
            $q->orderBy('numero', 'asc');
        }]);

        return view('horarios.show', compact('horario'));
    }

    // =========================================================================
    // 3. UPDATE (Modificar Horario y Recalcular Fichas)
    // =========================================================================

    /**
     * [UPDATE - Vista]: Muestra el formulario con los datos cargados
     */
    public function edit(HorarioAtencion $horario)
    {
        $horario->load('medico.persona');
        $diasSemana = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo'];

        return view('horarios.edit', compact('horario', 'diasSemana'));
    }

    /**
     * [UPDATE - Guardar]: Actualiza el rango horario y recalcula fichas
     */
    public function update(Request $request, HorarioAtencion $horario)
    {
        $request->validate([
            'dia_semana' => 'required|in:Lunes,Martes,Miercoles,Jueves,Viernes,Sabado,Domingo',
            'hora_inicio' => 'required',
            'hora_fin' => 'required|after:hora_inicio',
            'duracion_cita_min' => 'required|integer|min:10|max:120',
        ]);

        DB::transaction(function () use ($request, $horario) {
            $horario->update([
                'dia_semana' => $request->input('dia_semana'),
                'hora_inicio' => $request->input('hora_inicio'),
                'hora_fin' => $request->input('hora_fin'),
                'duracion_cita_min' => (int) $request->input('duracion_cita_min'),
            ]);

            // Eliminar fichas previas sin citas y regenerar
            $horario->fichas()->whereDoesntHave('citas')->delete();
            $this->generarFichas($horario);

            BitacoraService::registrar(
                accion: 'ACTUALIZAR_HORARIO',
                tabla: 'horarios_atencion',
                registroId: $horario->id,
                detalles: "Actualización de horario para Dr(a). {$horario->medico->nombre_completo}"
            );
        });

        return redirect()->route('horarios.show', $horario)->with('success', 'Horario y fichas actualizadas exitosamente.');
    }

    // =========================================================================
    // 4. DELETE (Eliminación / Desactivación Lógica RN11)
    // =========================================================================

    /**
     * [DELETE - Estándar Resource]: Elimina si no tiene citas o desactiva lógicamente (RN11)
     */
    public function destroy(HorarioAtencion $horario)
    {
        // Verificar si las fichas de este horario tienen citas agendadas
        $tieneCitas = Ficha::where('horario_id', $horario->id)->whereHas('citas')->exists();

        if ($tieneCitas) {
            $horario->update(['estado' => false]);
            $horario->fichas()->update(['estado' => false]);

            BitacoraService::registrar(
                accion: 'DESACTIVAR_HORARIO_RN11',
                tabla: 'horarios_atencion',
                registroId: $horario->id,
                detalles: "Desactivación lógica de jornada con citas asociadas (RN11)."
            );

            return redirect()->route('horarios.index')->with(
                'success',
                'El horario tiene citas médicas asociadas: fue desactivado lógicamente para preservar el historial.'
            );
        }

        DB::transaction(function () use ($horario) {
            $horarioId = $horario->id;
            $horario->fichas()->delete();
            $horario->delete();

            BitacoraService::registrar(
                accion: 'ELIMINAR_HORARIO',
                tabla: 'horarios_atencion',
                registroId: $horarioId,
                detalles: "Eliminación de jornada sin citas asociadas"
            );
        });

        return redirect()->route('horarios.index')->with('success', 'Horario y turnos eliminados correctamente.');
    }

    /**
     * [DELETE - Alternar Estado]: Habilitar o deshabilitar jornada
     */
    public function toggleEstado(HorarioAtencion $horario)
    {
        $nuevoEstado = !$horario->estado;

        DB::transaction(function () use ($horario, $nuevoEstado) {
            $horario->update(['estado' => $nuevoEstado]);
            $horario->fichas()->update(['estado' => $nuevoEstado]);

            BitacoraService::registrar(
                accion: 'CAMBIO_ESTADO_HORARIO',
                tabla: 'horarios_atencion',
                registroId: $horario->id,
                detalles: "Jornada del Dr(a). {$horario->medico->nombre_completo} marcada como " . ($nuevoEstado ? 'ACTIVA' : 'INACTIVA')
            );
        });

        return back()->with('success', 'Estado de la jornada actualizado correctamente.');
    }

    // =========================================================================
    // Métodos Auxiliares
    // =========================================================================

    /**
     * Genera en bucle las fichas predeterminadas (turnos de 30 min) para el horario
     */
    private function generarFichas(HorarioAtencion $horario): void
    {
        $inicio = Carbon::parse($horario->hora_inicio);
        $fin = Carbon::parse($horario->hora_fin);
        $duracion = $horario->duracion_cita_min ?? 30;
        $numero = 1;

        while ($inicio->copy()->addMinutes($duracion)->lte($fin)) {
            $slotFin = $inicio->copy()->addMinutes($duracion);

            Ficha::updateOrCreate(
                [
                    'horario_id' => $horario->id,
                    'numero' => $numero,
                ],
                [
                    'hora_inicio' => $inicio->format('H:i:s'),
                    'hora_fin' => $slotFin->format('H:i:s'),
                    'estado' => true,
                ]
            );

            $inicio = $slotFin;
            $numero++;
        }
    }
}
