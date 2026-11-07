<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\Consulta;
use App\Models\EstadoCita;
use App\Models\HistorialEstadoCita;
use App\Services\AccesoClinico;
use App\Services\BitacoraService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Controlador de Consultas (Paquete P7 - Atención Médica / CU23 Registrar atención médica)
 * El médico atiende sus citas del día; al finalizar, la cita pasa a Atendida (RN22).
 */
class ConsultaController extends Controller
{
    /**
     * [READ]: Citas del día por atender y últimas consultas registradas por el médico
     */
    public function index()
    {
        $medico = AccesoClinico::medicoActual();
        abort_if(!$medico, 403, 'Su usuario no está vinculado a un perfil de médico.');

        $pendientes = EstadoCita::whereIn('nombre', ['Programada', 'Confirmada'])->pluck('id');
        $porAtender = Cita::with(['paciente.persona', 'especialidad', 'estadoCita'])
            ->where('medico_id', $medico->id)
            ->whereDate('fecha', today())
            ->whereIn('estado_id', $pendientes)
            ->orderBy('hora_inicio')
            ->get();

        $recientes = Consulta::with(['cita.paciente.persona', 'receta'])
            ->whereHas('cita', fn ($q) => $q->where('medico_id', $medico->id))
            ->latest()
            ->limit(20)
            ->get();

        return view('consultas.index', compact('medico', 'porAtender', 'recientes'));
    }

    /**
     * [CREATE - Vista]: Formulario de atención; registra la hora real de inicio (CU23)
     */
    public function create(Request $request)
    {
        $cita = Cita::with(['paciente.persona', 'paciente.alergias', 'paciente.antecedentes', 'paciente.medicamentos', 'especialidad', 'estadoCita'])
            ->findOrFail($request->input('cita'));

        if ($error = $this->validarCitaAtendible($cita)) {
            return redirect()->route('consultas.index')->with('error', "Cita {$cita->codigo}: {$error}");
        }

        // Hora real de inicio de la atención (se guarda al finalizar)
        session()->put("atencion_inicio.{$cita->id}", now()->format('H:i:s'));

        return view('consultas.create', compact('cita'));
    }

    /**
     * [CREATE - Guardar]: Registra la consulta y cambia la cita a Atendida en una transacción (CU23 / RN22)
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'cita_id' => 'required|exists:citas,id',
            'sintomas' => 'nullable|string|max:2000',
            'peso' => 'nullable|numeric|between:0.5,400',
            'presion_arterial' => ['nullable', 'regex:/^\d{2,3}\/\d{2,3}$/'],
            'temperatura' => 'nullable|numeric|between:30,45',
            'diagnostico' => 'required|string|min:3|max:2000',
            'observaciones' => 'nullable|string|max:2000',
            'indicaciones' => 'nullable|string|max:2000',
        ], [
            'diagnostico.required' => 'El diagnóstico es obligatorio.',
            'peso.between' => 'El peso debe estar entre 0.5 y 400 kg.',
            'temperatura.between' => 'La temperatura debe estar entre 30 y 45 °C.',
            'presion_arterial.regex' => 'La presión arterial debe tener el formato 120/80.',
        ]);

        $cita = Cita::with('estadoCita')->findOrFail($data['cita_id']);
        if ($error = $this->validarCitaAtendible($cita)) {
            return redirect()->route('consultas.index')->with('error', "Cita {$cita->codigo}: {$error}");
        }

        try {
            $consulta = DB::transaction(function () use ($cita, $data) {
                $cita = Cita::with('estadoCita')->lockForUpdate()->findOrFail($cita->id);
                if ($cita->consulta()->exists()) {
                    throw new \DomainException('La cita ya tiene una consulta registrada (RN22).');
                }

                $consulta = Consulta::create([
                    'cita_id' => $cita->id,
                    'hora_inicio_real' => session("atencion_inicio.{$cita->id}", now()->format('H:i:s')),
                    'hora_fin_real' => now()->format('H:i:s'),
                    'sintomas' => $data['sintomas'] ?? null,
                    'peso' => $data['peso'] ?? null,
                    'presion_arterial' => $data['presion_arterial'] ?? null,
                    'temperatura' => $data['temperatura'] ?? null,
                    'diagnostico' => trim($data['diagnostico']),
                    'observaciones' => $data['observaciones'] ?? null,
                    'indicaciones' => $data['indicaciones'] ?? null,
                ]);

                $anterior = $cita->estado_id;
                $atendida = EstadoCita::where('nombre', 'Atendida')->value('id');
                $cita->update(['estado_id' => $atendida]);

                HistorialEstadoCita::create([
                    'cita_id' => $cita->id,
                    'estado_anterior_id' => $anterior,
                    'estado_nuevo_id' => $atendida,
                    'usuario_id' => Auth::id(),
                    'fecha_hora' => now(),
                    'motivo' => 'Atención médica registrada. Diagnóstico: ' . \Illuminate\Support\Str::limit($consulta->diagnostico, 120),
                ]);

                BitacoraService::registrar(
                    accion: 'REGISTRAR_ATENCION',
                    tabla: 'consultas',
                    registroId: $consulta->id,
                    detalles: "Consulta registrada para la cita {$cita->codigo}; la cita pasa a Atendida."
                );

                return $consulta;
            });
        } catch (\DomainException $e) {
            return redirect()->route('consultas.index')->with('error', $e->getMessage());
        } catch (QueryException $e) {
            $msg = $e->getPrevious()?->getMessage() ?? $e->getMessage();
            $msg = preg_match('/ERROR:\s+(.*?)(\n|CONTEXT|\(SQL|$)/s', $msg, $m) ? trim($m[1]) : 'error de base de datos.';
            return back()->withInput()->with('error', 'No se pudo registrar la atención: ' . $msg);
        }

        session()->forget("atencion_inicio.{$cita->id}");

        return redirect()->route('consultas.show', $consulta)
            ->with('success', "Atención de la cita {$cita->codigo} registrada. La cita quedó Atendida.");
    }

    /**
     * [READ - Detalle]: Consulta registrada, con acceso a la receta (CU25)
     */
    public function show(Consulta $consulta)
    {
        $consulta->load(['cita.paciente.persona', 'cita.medico.persona', 'cita.especialidad', 'receta.detalles.medicamento', 'antecedentes']);
        AccesoClinico::autorizarPaciente($consulta->cita->paciente);
        if (AccesoClinico::rol() === 'Medico') {
            abort_if((int) $consulta->cita->medico_id !== (int) AccesoClinico::medicoActual()?->id, 403, 'La consulta pertenece a otro médico.');
        }

        return view('consultas.show', compact('consulta'));
    }

    /** Precondiciones del CU23: cita del médico, Programada/Confirmada, de hoy y sin consulta. */
    private function validarCitaAtendible(Cita $cita): ?string
    {
        $medico = AccesoClinico::medicoActual();
        if (!$medico || (int) $cita->medico_id !== (int) $medico->id) {
            abort(403, 'Acceso denegado: la cita no pertenece a su agenda.');
        }
        $estado = $cita->estadoCita->nombre;
        if (!in_array($estado, ['Programada', 'Confirmada'], true)) {
            return "está {$estado}: no se puede atender.";
        }
        if (!$cita->fecha->isSameDay(today())) {
            return 'solo se atienden las citas de la fecha actual (' . $cita->fecha->format('d/m/Y') . ').';
        }
        if ($cita->consulta()->exists()) {
            return 'ya tiene una consulta registrada (RN22).';
        }
        return null;
    }
}
