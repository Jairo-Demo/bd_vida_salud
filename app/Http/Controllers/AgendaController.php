<?php

namespace App\Http\Controllers;

use App\Models\BloqueoAgenda;
use App\Models\Cita;
use App\Models\EstadoCita;
use App\Models\HorarioAtencion;
use App\Models\Medico;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Controlador de Agenda (Paquete P5 - Agenda y Asistencia / CU20 Consultar agenda y citas)
 * Muestra por ficha las citas de un médico en una fecha, con resumen semanal (flujo alterno A).
 * Las acciones de confirmar e inasistencia están en CitaEstadoController (CU18, CU19).
 */
class AgendaController extends Controller
{
    private const DIAS = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miercoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sabado', 7 => 'Domingo'];

    /**
     * [READ - Agenda]: Agenda del día por médico, fecha y estado (CU20 / RN28)
     */
    public function index(Request $request)
    {
        $request->validate([
            'fecha' => 'nullable|date',
            'medico_id' => 'nullable|integer|exists:medicos,id',
            'estado_id' => 'nullable|integer|exists:estados_cita,id',
        ]);

        $fecha = $request->input('fecha', today()->toDateString());
        $estadoId = $request->input('estado_id');
        $esMedico = Auth::user()->rol->nombre === 'Medico';

        // RN28: el médico solo ve su propia agenda
        if ($esMedico) {
            $miMedico = Medico::where('persona_id', Auth::user()->persona_id)->first();
            abort_if(!$miMedico, 403, 'Su usuario no está vinculado a un perfil de médico.');
            if ($request->filled('medico_id') && (int) $request->input('medico_id') !== $miMedico->id) {
                abort(403, 'Acceso denegado: un médico solo puede consultar su propia agenda.');
            }
            $medicoId = $miMedico->id;
        } else {
            $medicoId = $request->input('medico_id');
        }

        $medicos = Medico::with('persona')->where('estado', true)->get();
        $estados = EstadoCita::orderBy('id')->get();
        $medico = $medicoId ? Medico::with(['persona', 'especialidades', 'horarios'])->find($medicoId) : null;

        $dia = self::DIAS[Carbon::parse($fecha)->dayOfWeekIso];
        $filas = collect();
        $horario = null;
        $bloqueo = null;
        $diasAtencion = [];
        $semana = [];
        $citasDelDia = collect();

        if ($medico) {
            $horario = HorarioAtencion::where('medico_id', $medico->id)->where('dia_semana', $dia)->where('estado', true)->first();
            $diasAtencion = $medico->horarios->where('estado', true)->pluck('dia_semana')->unique()->values()->all();
            $bloqueo = $this->bloqueo($medico->id, $fecha);

            if ($horario) {
                $citas = Cita::with(['paciente.persona', 'especialidad', 'estadoCita', 'consulta'])
                    ->where('medico_id', $medico->id)
                    ->whereDate('fecha', $fecha)
                    ->get()
                    ->groupBy('ficha_id');

                foreach ($horario->fichas()->where('estado', true)->orderBy('numero')->get() as $ficha) {
                    $delaFicha = $citas->get($ficha->id, collect());
                    // La cita activa ocupa la ficha; las canceladas/inasistencias se muestran como historial
                    $activa = $delaFicha->first(fn ($c) => !in_array($c->estadoCita->nombre, ['Cancelada', 'Inasistencia']));
                    $mostrar = $activa ? collect([$activa]) : $delaFicha;

                    if ($mostrar->isEmpty()) {
                        if (!$estadoId) {
                            $filas->push(['ficha' => $ficha, 'cita' => null, 'libre' => !$bloqueo]);
                        }
                        continue;
                    }
                    foreach ($mostrar as $cita) {
                        if ($estadoId && (int) $cita->estado_id !== (int) $estadoId) {
                            continue;
                        }
                        $filas->push(['ficha' => $ficha, 'cita' => $cita, 'libre' => false]);
                    }
                }
            }

            $semana = $this->resumenSemanal($medico->id, $fecha);
        } else {
            // Sin médico seleccionado: todas las citas del día (solo recepción/administración)
            $citasDelDia = Cita::with(['paciente.persona', 'medico.persona', 'especialidad', 'estadoCita'])
                ->whereDate('fecha', $fecha)
                ->when($estadoId, fn ($q) => $q->where('estado_id', $estadoId))
                ->orderBy('hora_inicio')
                ->get();
        }

        // Indicadores del día
        $base = Cita::whereDate('fecha', $fecha)->when($medicoId, fn ($q) => $q->where('medico_id', $medicoId));
        $conteo = (clone $base)->join('estados_cita', 'estados_cita.id', '=', 'citas.estado_id')
            ->selectRaw('estados_cita.nombre, count(*) as total')->groupBy('estados_cita.nombre')
            ->pluck('total', 'nombre');

        return view('agenda.index', compact(
            'fecha', 'dia', 'medicoId', 'estadoId', 'esMedico', 'medicos', 'estados', 'medico',
            'horario', 'bloqueo', 'diasAtencion', 'filas', 'semana', 'citasDelDia', 'conteo'
        ));
    }

    /** Resumen de fichas ocupadas y libres por día de la semana (CU20 - flujo alterno A). */
    private function resumenSemanal(int $medicoId, string $fecha): array
    {
        $inicio = Carbon::parse($fecha)->startOfWeek(Carbon::MONDAY);
        $horarios = HorarioAtencion::withCount(['fichas' => fn ($q) => $q->where('estado', true)])
            ->where('medico_id', $medicoId)->where('estado', true)->get()->keyBy('dia_semana');
        $liberan = EstadoCita::whereIn('nombre', ['Cancelada', 'Inasistencia'])->pluck('id');

        $ocupadas = Cita::where('medico_id', $medicoId)
            ->whereBetween('fecha', [$inicio->toDateString(), $inicio->copy()->addDays(6)->toDateString()])
            ->whereNotIn('estado_id', $liberan)
            ->selectRaw('fecha, count(*) as total')->groupBy('fecha')
            ->pluck('total', 'fecha')
            ->mapWithKeys(fn ($v, $k) => [Carbon::parse($k)->toDateString() => $v]);

        $semana = [];
        for ($i = 0; $i < 7; $i++) {
            $d = $inicio->copy()->addDays($i);
            $nombre = self::DIAS[$d->dayOfWeekIso];
            $total = $horarios->has($nombre) ? $horarios[$nombre]->fichas_count : 0;
            $ocup = $ocupadas[$d->toDateString()] ?? 0;
            $semana[] = [
                'fecha' => $d->toDateString(),
                'dia' => $nombre,
                'atiende' => $horarios->has($nombre),
                'bloqueado' => (bool) $this->bloqueo($medicoId, $d->toDateString()),
                'ocupadas' => $ocup,
                'libres' => max($total - $ocup, 0),
            ];
        }
        return $semana;
    }

    /** Bloqueo de agenda de día completo vigente para el médico (CU30). */
    private function bloqueo(int $medicoId, string $fecha): ?BloqueoAgenda
    {
        return BloqueoAgenda::where('estado', true)
            ->whereDate('fecha_inicio', '<=', $fecha)->whereDate('fecha_fin', '>=', $fecha)
            ->where(fn ($q) => $q->whereNull('medico_id')->orWhere('medico_id', $medicoId))
            ->first();
    }
}
