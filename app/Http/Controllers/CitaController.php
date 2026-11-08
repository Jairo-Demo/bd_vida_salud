<?php

namespace App\Http\Controllers;

use App\Models\BloqueoAgenda;
use App\Models\Cita;
use App\Models\Especialidad;
use App\Models\EstadoCita;
use App\Models\Ficha;
use App\Models\HistorialEstadoCita;
use App\Models\HorarioAtencion;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\Recordatorio;
use App\Services\BitacoraService;
use App\Services\RecordatorioService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
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

        // El paciente solo puede agendarse citas a sí mismo
        if (Auth::user()->rol->nombre === 'Paciente') {
            $propio = $this->pacienteAutenticadoId();
            abort_if(!$propio, 403, 'Su usuario no está vinculado a un paciente.');
            $pacientes = $pacientes->where('id', $propio)->values();
            $pacientePre = $propio;
        }
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

        // El paciente solo puede agendarse citas a sí mismo
        if (Auth::user()->rol->nombre === 'Paciente' && (int) $pacienteId !== (int) $this->pacienteAutenticadoId()) {
            return back()->withInput()->withErrors(['paciente_id' => 'Solo puede agendar citas a su propio nombre.']);
        }
        $fecha = $request->input('fecha');
        $fichaId = $request->input('ficha_id');

        // 2. Obtener datos de la Ficha seleccionada
        $ficha = Ficha::with('horario')->findOrFail($fichaId);

        // 2.0 La ficha debe pertenecer a una jornada activa del médico para ese día de la semana (RN13)
        if (!$ficha->horario || (int) $ficha->horario->medico_id !== (int) $medicoId
            || $ficha->horario->dia_semana !== $this->diaSemana($fecha) || !$ficha->horario->estado || !$ficha->estado) {
            return back()->withInput()->withErrors([
                'ficha_id' => 'La ficha seleccionada está fuera del horario de atención del médico para esa fecha (RN13).',
            ]);
        }

        // 2.1 La fecha no debe estar bloqueada en la agenda del médico (CU30 / RN)
        if ($bloqueo = $this->bloqueoQueAfecta($medicoId, $fecha, $ficha)) {
            return back()->withInput()->withErrors([
                'fecha' => "La agenda del médico está bloqueada en esa fecha ({$bloqueo->tipo}: {$bloqueo->motivo}).",
            ]);
        }

        // 4. Obtener Estado inicial "Programada"
        $estadoProgramada = EstadoCita::firstOrCreate(
            ['nombre' => 'Programada'],
            ['descripcion' => 'Cita reservada pendiente de confirmación']
        );

        // 5. Transacción atómica de guardado con bloqueo pesimista anti-concurrencia (RN07, RN13, RNF09)
        try {
            $cita = DB::transaction(function () use ($request, $fichaId, $medicoId, $estadoProgramada, $fecha) {
                // A. Bloqueo pesimista de fila en PostgreSQL (SELECT ... FOR UPDATE) para serializar reservas concurrentes
                $fichaBloqueada = Ficha::where('id', $fichaId)->lockForUpdate()->firstOrFail();

                // B. Verificación atómica anti-solapamiento con la ficha bloqueada (RN07, RN09)
                $solapada = Cita::where('medico_id', $medicoId)
                    ->where('fecha', $fecha)
                    ->where('ficha_id', $fichaId)
                    ->whereNotIn('estado_id', $this->estadosQueLiberanFicha())
                    ->exists();

                if ($solapada) {
                    throw new \DomainException('Conflicto de horario: El turno seleccionado acaba de ser reservado por otro usuario. Por favor seleccione otro turno (RN07).');
                }

                // C. Autogenerar código único correlativo (ej. CM-0001)
                $ultimoId = Cita::max('id') ?? 0;
                $codigo = 'CM-' . str_pad($ultimoId + 1, 4, '0', STR_PAD_LEFT);

                // D. Crear registro central de Cita
                $nuevaCita = Cita::create([
                    'codigo' => $codigo,
                    'paciente_id' => $request->input('paciente_id'),
                    'medico_id' => $request->input('medico_id'),
                    'especialidad_id' => $request->input('especialidad_id'),
                    'ficha_id' => $fichaBloqueada->id,
                    'estado_id' => $estadoProgramada->id,
                    'usuario_registro_id' => Auth::id(),
                    'fecha' => $fecha,
                    'hora_inicio' => $fichaBloqueada->hora_inicio,
                    'hora_fin' => $fichaBloqueada->hora_fin,
                    'motivo' => trim($request->input('motivo')),
                    'modalidad' => $request->input('modalidad'),
                    'fecha_creacion' => now(),
                ]);

                // E. Registro inicial inmutable en historial_estados_cita
                HistorialEstadoCita::create([
                    'cita_id' => $nuevaCita->id,
                    'estado_anterior_id' => null,
                    'estado_nuevo_id' => $estadoProgramada->id,
                    'usuario_id' => Auth::id(),
                    'fecha_hora' => now(),
                    'motivo' => 'Registro y programación inicial de la cita médica.',
                ]);

                // F. Auditoría en Bitácora
                BitacoraService::registrar(
                    accion: 'PROGRAMAR_CITA',
                    tabla: 'citas',
                    registroId: $nuevaCita->id,
                    detalles: "Cita programada {$codigo} para {$nuevaCita->paciente->nombre_completo} con Dr(a). {$nuevaCita->medico->nombre_completo} el {$fecha} ({$fichaBloqueada->hora_inicio}-{$fichaBloqueada->hora_fin})"
                );

                // G. Recordatorio automático 24 horas antes (CU31 - alterno A)
                RecordatorioService::programarProximaCita($nuevaCita);

                return $nuevaCita;
            });
        } catch (\DomainException $e) {
            return back()->withInput()->withErrors(['ficha_id' => $e->getMessage()]);
        } catch (QueryException $e) {
            // Error de concurrencia capturado por el trigger trg_validar_ficha_libre o constraint UNIQUE en PostgreSQL
            return back()->withInput()->withErrors([
                'ficha_id' => 'Conflicto de horario concurrente: El turno seleccionado fue reservado simultáneamente por otro usuario (RN07).',
            ]);
        }

        return redirect()->route('citas.show', $cita)->with('success', "Cita médica {$cita->codigo} agendada exitosamente.");

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
        } elseif (Auth::user()->rol->nombre === 'Paciente') {
            // El paciente solo ve sus propias citas (CU17 / privacidad)
            $query->where('paciente_id', $this->pacienteAutenticadoId() ?? 0);
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
        $this->autorizarPacientePropietario($cita);

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
    // 3. UPDATE (Reprogramar Cita - CU16 / RF08)
    // =========================================================================

    /**
     * [UPDATE - Vista]: Formulario para reprogramar la cita a una nueva fecha y ficha libre (CU16)
     */
    public function editReprogramar(Cita $cita)
    {
        $cita->load(['paciente.persona', 'medico.persona', 'especialidad', 'estadoCita', 'ficha']);

        if (!$this->esReprogramableOCancelable($cita)) {
            return redirect()->route('citas.show', $cita)
                ->with('error', "La cita {$cita->codigo} está {$cita->estadoCita->nombre}: solo se reprograman citas Programadas o Confirmadas (RN20).");
        }

        return view('citas.reprogramar', compact('cita'));
    }

    /**
     * [UPDATE - Guardar]: Cambia fecha y ficha validando estado, horario, bloqueos y disponibilidad (CU16 / RN13)
     */
    public function reprogramar(Request $request, Cita $cita)
    {
        $request->validate([
            'fecha' => 'required|date|after_or_equal:today',
            'ficha_id' => 'required|exists:fichas,id',
            'motivo' => 'required|string|min:5|max:500',
        ], [
            'fecha.after_or_equal' => 'No se puede reprogramar a una fecha pasada (RN06).',
            'ficha_id.required' => 'Debe seleccionar una ficha libre para la nueva fecha.',
            'motivo.required' => 'Indique el motivo de la reprogramación.',
            'motivo.min' => 'El motivo debe tener al menos 5 caracteres.',
        ]);

        $fecha = $request->input('fecha');
        $ficha = Ficha::with('horario')->findOrFail($request->input('ficha_id'));
        $motivo = trim($request->input('motivo'));

        try {
            $cita = DB::transaction(function () use ($cita, $fecha, $ficha, $motivo) {
                // Bloqueo pesimista de la cita para evitar dos reprogramaciones simultáneas
                $cita = Cita::with(['estadoCita', 'paciente.persona'])->lockForUpdate()->findOrFail($cita->id);

                // a) Estado reprogramable (RN20)
                if (!$this->esReprogramableOCancelable($cita)) {
                    throw new \DomainException("La cita está {$cita->estadoCita->nombre}: no se puede reprogramar.");
                }

                // b) La ficha pertenece a una jornada activa del mismo médico para ese día de la semana
                $dia = $this->diaSemana($fecha);
                if (!$ficha->horario || (int) $ficha->horario->medico_id !== (int) $cita->medico_id
                    || $ficha->horario->dia_semana !== $dia || !$ficha->horario->estado || !$ficha->estado) {
                    throw new \DomainException('La ficha seleccionada no corresponde al horario del médico en esa fecha.');
                }

                // c) No es la misma fecha y ficha actual
                if ($cita->fecha->toDateString() === $fecha && (int) $cita->ficha_id === (int) $ficha->id) {
                    throw new \DomainException('Seleccione una fecha o ficha diferente a la actual.');
                }

                // d) Si es hoy, la ficha no debe haber pasado
                if ($fecha === today()->toDateString() && substr($ficha->hora_inicio, 0, 5) <= now()->format('H:i')) {
                    throw new \DomainException('La ficha seleccionada ya pasó. Elija un horario posterior.');
                }

                // e) Bloqueos de agenda (CU30)
                if ($bloqueo = $this->bloqueoQueAfecta($cita->medico_id, $fecha, $ficha)) {
                    throw new \DomainException("La agenda del médico está bloqueada en esa fecha ({$bloqueo->tipo}: {$bloqueo->motivo}).");
                }

                // f) Ficha libre (RN07). La BD lo refuerza con trg_validar_ficha_libre.
                $ocupada = Cita::where('ficha_id', $ficha->id)
                    ->where('fecha', $fecha)
                    ->where('id', '!=', $cita->id)
                    ->whereNotIn('estado_id', $this->estadosQueLiberanFicha())
                    ->exists();
                if ($ocupada) {
                    throw new \DomainException('La ficha seleccionada acaba de ser ocupada por otra cita. Elija otra (RN07).');
                }

                $anterior = $cita->fecha->format('d/m/Y') . ' ' . substr($cita->hora_inicio, 0, 5);
                $nuevo = Carbon::parse($fecha)->format('d/m/Y') . ' ' . substr($ficha->hora_inicio, 0, 5);

                // g) Actualizar fecha, ficha y horario conservando el código
                $cita->update([
                    'fecha' => $fecha,
                    'ficha_id' => $ficha->id,
                    'hora_inicio' => $ficha->hora_inicio,
                    'hora_fin' => $ficha->hora_fin,
                ]);

                // h) Historial (el estado se mantiene) y bitácora
                HistorialEstadoCita::create([
                    'cita_id' => $cita->id,
                    'estado_anterior_id' => $cita->estado_id,
                    'estado_nuevo_id' => $cita->estado_id,
                    'usuario_id' => Auth::id(),
                    'fecha_hora' => now(),
                    'motivo' => "Reprogramación de {$anterior} a {$nuevo}. Motivo: {$motivo}",
                ]);

                BitacoraService::registrar(
                    accion: 'REPROGRAMAR_CITA',
                    tabla: 'citas',
                    registroId: $cita->id,
                    detalles: "Cita {$cita->codigo} reprogramada de {$anterior} a {$nuevo}. Motivo: {$motivo}"
                );

                // i) Aviso al paciente y nuevo recordatorio 24 h antes (CU31)
                RecordatorioService::programarProximaCita($cita->fresh());
                Recordatorio::create([
                    'cita_id' => $cita->id,
                    'tipo' => 'Reprogramacion',
                    'mensaje' => "Su cita {$cita->codigo} fue reprogramada para el {$nuevo}.",
                    'fecha_programada' => now(),
                    'estado' => 'Pendiente',
                ]);

                return $cita;
            });
        } catch (\DomainException $e) {
            return back()->withInput()->withErrors(['ficha_id' => $e->getMessage()]);
        } catch (QueryException $e) {
            return back()->withInput()->withErrors(['ficha_id' => 'No se pudo reprogramar: ' . $this->mensajeBD($e)]);
        }

        return redirect()->route('citas.show', $cita)
            ->with('success', "Cita {$cita->codigo} reprogramada correctamente. La ficha anterior quedó libre.");
    }

    // =========================================================================
    // 4. DELETE LÓGICO (Cancelar Cita - CU17 / RF09) — la cita no se elimina
    // =========================================================================

    /**
     * [CANCELAR - Vista]: Muestra la cita y solicita el motivo de cancelación (CU17)
     */
    public function editCancelar(Cita $cita)
    {
        $this->autorizarCancelacion($cita);
        $cita->load(['paciente.persona', 'medico.persona', 'especialidad', 'estadoCita', 'ficha']);

        if (!$this->esReprogramableOCancelable($cita)) {
            return redirect()->route('citas.show', $cita)
                ->with('error', "La cita {$cita->codigo} está {$cita->estadoCita->nombre}: solo se cancelan citas Programadas o Confirmadas (RN20).");
        }

        return view('citas.cancelar', compact('cita'));
    }

    /**
     * [CANCELAR - Guardar]: Cambia el estado a Cancelada con su motivo, historial y bitácora (CU17 / RN09, RN21)
     * En PostgreSQL usa el procedimiento almacenado sp_cancelar_cita (Word Cap. 6.2.3.3).
     */
    public function cancelar(Request $request, Cita $cita)
    {
        $this->autorizarCancelacion($cita);

        $request->validate([
            'motivo_cancelacion' => 'required|string|min:5|max:500',
        ], [
            'motivo_cancelacion.required' => 'Indique el motivo de la cancelación (RN21).',
            'motivo_cancelacion.min' => 'El motivo debe tener al menos 5 caracteres.',
        ]);

        $motivo = trim($request->input('motivo_cancelacion'));

        try {
            DB::transaction(function () use ($cita, $motivo) {
                $cita = Cita::with('estadoCita')->lockForUpdate()->findOrFail($cita->id);

                if (!$this->esReprogramableOCancelable($cita)) {
                    throw new \DomainException("La cita está {$cita->estadoCita->nombre}: no se puede cancelar (RN20).");
                }

                if (DB::getDriverName() === 'pgsql') {
                    // Estado Cancelada + motivo + historial + bitácora en la base de datos
                    DB::statement('CALL sp_cancelar_cita(?, ?, ?)', [$cita->id, Auth::id(), $motivo]);
                } else {
                    $cancelada = EstadoCita::where('nombre', 'Cancelada')->value('id');
                    $anterior = $cita->estado_id;
                    $cita->update(['estado_id' => $cancelada, 'motivo_cancelacion' => $motivo]);
                    HistorialEstadoCita::create([
                        'cita_id' => $cita->id, 'estado_anterior_id' => $anterior, 'estado_nuevo_id' => $cancelada,
                        'usuario_id' => Auth::id(), 'fecha_hora' => now(), 'motivo' => $motivo,
                    ]);
                    BitacoraService::registrar('CANCELAR_CITA', 'citas', $cita->id, "Cita {$cita->codigo} cancelada. Motivo: {$motivo}");
                }

                // Aviso de cancelación al paciente; el recordatorio de próxima cita ya no se envía (CU31)
                RecordatorioService::anularProximaCita($cita);
                Recordatorio::create([
                    'cita_id' => $cita->id,
                    'tipo' => 'Cancelacion',
                    'mensaje' => "Su cita {$cita->codigo} del " . $cita->fecha->format('d/m/Y') . ' a las ' . substr($cita->hora_inicio, 0, 5) . ' fue cancelada.',
                    'fecha_programada' => now(),
                    'estado' => 'Pendiente',
                ]);
            });
        } catch (\DomainException $e) {
            return redirect()->route('citas.show', $cita)->with('error', $e->getMessage());
        } catch (QueryException $e) {
            return back()->withInput()->withErrors(['motivo_cancelacion' => 'No se pudo cancelar: ' . $this->mensajeBD($e)]);
        }

        return redirect()->route('citas.show', $cita)
            ->with('success', "Cita {$cita->codigo} cancelada. La ficha quedó disponible para otros pacientes.");
    }

    // =========================================================================
    // Validaciones compartidas (ControlValidacionCita / ControlValidacionTransicion)
    // =========================================================================

    /** Solo las citas Programadas o Confirmadas se reprograman o cancelan (RN20). */
    private function esReprogramableOCancelable(Cita $cita): bool
    {
        $estado = $cita->estadoCita->nombre ?? EstadoCita::find($cita->estado_id)?->nombre;
        return in_array($estado, ['Programada', 'Confirmada'], true);
    }

    /** Estados que no ocupan ficha (RN09): Cancelada e Inasistencia. */
    private function estadosQueLiberanFicha(): array
    {
        return EstadoCita::whereIn('nombre', ['Cancelada', 'Inasistencia'])->pluck('id')->all();
    }

    /** Id del paciente vinculado al usuario autenticado (rol Paciente). */
    private function pacienteAutenticadoId(): ?int
    {
        return Paciente::where('persona_id', Auth::user()->persona_id)->value('id');
    }

    /** El paciente solo puede ver o cancelar sus propias citas (CU17 - excepción). */
    private function autorizarPacientePropietario(Cita $cita): void
    {
        if (Auth::user()->rol->nombre === 'Paciente' && (int) $cita->paciente_id !== (int) $this->pacienteAutenticadoId()) {
            abort(403, 'Acceso denegado: la cita no le pertenece.');
        }
    }

    /** Cancelan: Administrador, Recepcionista y el Paciente dueño de la cita (actores del CU17). */
    private function autorizarCancelacion(Cita $cita): void
    {
        if (!in_array(Auth::user()->rol->nombre, ['Administrador', 'Recepcionista', 'Paciente'], true)) {
            abort(403, 'Acceso denegado: su rol no puede cancelar citas.');
        }
        $this->autorizarPacientePropietario($cita);
    }

    /** Bloqueo de agenda activo que afecta al médico en la fecha (y a la ficha si el bloqueo es por horas). */
    private function bloqueoQueAfecta($medicoId, string $fecha, ?Ficha $ficha = null): ?BloqueoAgenda
    {
        return BloqueoAgenda::vigentesPara($medicoId, $fecha)->get()
            ->first(fn ($b) => !$ficha || $b->afectaHorario($ficha->hora_inicio, $ficha->hora_fin));
    }

    private function diaSemana(string $fecha): string
    {
        $dias = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miercoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sabado', 7 => 'Domingo'];
        return $dias[Carbon::parse($fecha)->dayOfWeekIso];
    }

    /** Extrae el mensaje legible de una excepción lanzada por un trigger de PostgreSQL. */
    private function mensajeBD(QueryException $e): string
    {
        $msg = $e->getPrevious()?->getMessage() ?? $e->getMessage();
        if (preg_match('/ERROR:\s+(.*?)(\n|CONTEXT|\(SQL|$)/s', $msg, $m)) {
            return trim($m[1]);
        }
        return 'error de base de datos.';
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

        // Fichas ocupadas en esa fecha: citas activas (Cancelada e Inasistencia liberan la ficha - RN09)
        // Al reprogramar se excluye la propia cita (excluir_cita_id).
        $fichasOcupadas = Cita::where('medico_id', $medicoId)
            ->where('fecha', $fecha)
            ->whereNotIn('estado_id', $this->estadosQueLiberanFicha())
            ->when($request->filled('excluir_cita_id'), fn ($q) => $q->where('id', '!=', $request->input('excluir_cita_id')))
            ->pluck('ficha_id')
            ->toArray();

        $esHoy = $fecha === today()->toDateString();
        $horaActual = now()->format('H:i');

        // Mapear fichas del horario con su disponibilidad y el motivo cuando no está libre
        $fichasResultado = $horario->fichas()->where('estado', true)->orderBy('numero')->get()->map(function ($f) use ($fichasOcupadas, $medicoId, $fecha, $esHoy, $horaActual) {
            $motivo = null;
            if (in_array($f->id, $fichasOcupadas)) {
                $motivo = 'Ocupada';
            } elseif ($esHoy && substr($f->hora_inicio, 0, 5) <= $horaActual) {
                $motivo = 'Hora pasada';
            } elseif ($this->bloqueoQueAfecta($medicoId, $fecha, $f)) {
                $motivo = 'Bloqueada';
            }

            return [
                'id' => $f->id,
                'numero' => $f->numero,
                'hora_inicio' => substr($f->hora_inicio, 0, 5),
                'hora_fin' => substr($f->hora_fin, 0, 5),
                'disponible' => $motivo === null,
                'motivo' => $motivo,
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
