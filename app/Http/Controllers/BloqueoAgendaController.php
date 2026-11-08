<?php

namespace App\Http\Controllers;

use App\Models\BloqueoAgenda;
use App\Models\Cita;
use App\Models\EstadoCita;
use App\Models\Medico;
use App\Services\BitacoraService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Controlador de Bloqueos de Agenda (Paquete P3 / CU30 Gestionar bloqueos de agenda)
 * Feriados (generales, sin médico), vacaciones, permisos u otros. Las fichas del período
 * bloqueado dejan de ofrecerse en la consulta de disponibilidad (CU14).
 */
class BloqueoAgendaController extends Controller
{
    /**
     * [READ]: Bloqueos registrados (vigentes por defecto) y formulario de registro
     */
    public function index(Request $request)
    {
        $ver = $request->input('ver', 'vigentes');
        $medicoId = $request->input('medico_id');

        $bloqueos = BloqueoAgenda::with(['medico.persona', 'usuario'])
            ->when($ver === 'vigentes', fn ($q) => $q->where('estado', true)->whereDate('fecha_fin', '>=', today()))
            ->when($medicoId === 'general', fn ($q) => $q->whereNull('medico_id'))
            ->when($medicoId && $medicoId !== 'general', fn ($q) => $q->where('medico_id', $medicoId))
            ->orderBy('fecha_inicio')
            ->paginate(15)->withQueryString();

        // Citas activas afectadas por cada bloqueo de la página
        $afectadas = $bloqueos->getCollection()->mapWithKeys(fn ($b) => [$b->id => $b->estado ? $this->citasAfectadas($b)->count() : 0]);
        $medicos = Medico::with('persona')->where('estado', true)->get();

        return view('bloqueos.index', compact('bloqueos', 'afectadas', 'medicos', 'ver', 'medicoId'));
    }

    /**
     * [CREATE]: Registra el bloqueo; si hay citas activas en el período, advierte y pide confirmar (CU30)
     */
    public function store(Request $request)
    {
        $data = $this->validar($request);
        $bloqueo = new BloqueoAgenda($data + ['usuario_id' => Auth::id(), 'estado' => true]);

        $afectadas = $this->citasAfectadas($bloqueo);
        if ($afectadas->isNotEmpty() && !$request->boolean('confirmar')) {
            return back()->withInput()->with('afectadas', $afectadas->map(fn ($c) => [
                'codigo' => $c->codigo,
                'fecha' => $c->fecha->format('d/m/Y') . ' ' . substr($c->hora_inicio, 0, 5),
                'paciente' => $c->paciente->nombre_completo,
                'medico' => $c->medico->persona->nombre_completo ?? '',
                'estado' => $c->estadoCita->nombre,
            ])->all());
        }

        $bloqueo->save();
        BitacoraService::registrar(
            accion: 'REGISTRAR_BLOQUEO',
            tabla: 'bloqueos_agenda',
            registroId: $bloqueo->id,
            detalles: "Bloqueo {$bloqueo->tipo} " . ($bloqueo->medico_id ? "del médico #{$bloqueo->medico_id}" : 'general')
                . " {$bloqueo->descripcion_periodo}: {$bloqueo->motivo}. Citas afectadas: {$afectadas->count()}."
        );

        if ($afectadas->isNotEmpty()) {
            return redirect()->route('bloqueos.show', $bloqueo)
                ->with('success', "Bloqueo registrado. Hay {$afectadas->count()} cita(s) en el período: reprográmelas (CU16).");
        }
        return redirect()->route('bloqueos.index')->with('success', 'Bloqueo registrado. Las fichas del período ya no se ofrecen.');
    }

    /**
     * [READ]: Detalle del bloqueo con las citas activas del período para reprogramar (CU16)
     */
    public function show(BloqueoAgenda $bloqueo)
    {
        $bloqueo->load(['medico.persona', 'usuario']);
        $citas = $bloqueo->estado ? $this->citasAfectadas($bloqueo) : collect();

        return view('bloqueos.show', compact('bloqueo', 'citas'));
    }

    /**
     * [CU30 - Alterno A]: Activa o desactiva el bloqueo; al desactivarlo las fichas vuelven a ofrecerse
     */
    public function toggleEstado(BloqueoAgenda $bloqueo)
    {
        $bloqueo->update(['estado' => !$bloqueo->estado]);
        $txt = $bloqueo->estado ? 'activado' : 'desactivado: las fichas del período vuelven a ofrecerse';
        BitacoraService::registrar('ESTADO_BLOQUEO', 'bloqueos_agenda', $bloqueo->id, "Bloqueo {$bloqueo->tipo} {$bloqueo->descripcion_periodo} {$txt}.");

        return back()->with('success', "Bloqueo {$txt}.");
    }

    // -------------------------------------------------------------------------

    private function validar(Request $request): array
    {
        $data = $request->validate([
            'medico_id' => 'nullable|exists:medicos,id',
            'tipo' => 'required|in:Feriado,Vacaciones,Permiso,Otro',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'hora_inicio' => 'nullable|required_with:hora_fin|date_format:H:i',
            'hora_fin' => 'nullable|required_with:hora_inicio|date_format:H:i|after:hora_inicio',
            'motivo' => 'required|string|min:3|max:255',
        ], [
            'fecha_fin.after_or_equal' => 'La fecha final no puede ser anterior a la fecha inicial.',
            'hora_fin.after' => 'La hora final debe ser posterior a la hora inicial.',
            'hora_inicio.required_with' => 'Indique ambas horas o deje las dos vacías (bloqueo de día completo).',
            'hora_fin.required_with' => 'Indique ambas horas o deje las dos vacías (bloqueo de día completo).',
        ]);
        $data['medico_id'] = $data['medico_id'] ?? null;
        $data['motivo'] = trim($data['motivo']);
        return $data;
    }

    /** Citas Programadas o Confirmadas dentro del período (y horas) del bloqueo. */
    private function citasAfectadas(BloqueoAgenda $b): Collection
    {
        $activos = EstadoCita::whereIn('nombre', ['Programada', 'Confirmada'])->pluck('id');

        return Cita::with(['paciente.persona', 'medico.persona', 'estadoCita'])
            ->whereIn('estado_id', $activos)
            ->whereBetween('fecha', [$b->fecha_inicio->toDateString(), $b->fecha_fin->toDateString()])
            ->when($b->medico_id, fn ($q) => $q->where('medico_id', $b->medico_id))
            ->orderBy('fecha')->orderBy('hora_inicio')
            ->get()
            ->filter(fn ($c) => $b->afectaHorario($c->hora_inicio, $c->hora_fin))
            ->values();
    }
}
