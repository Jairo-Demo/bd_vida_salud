<?php

namespace App\Http\Controllers;

use App\Models\Consulta;
use App\Models\Paciente;
use App\Services\AccesoClinico;
use App\Services\BitacoraService;
use Illuminate\Http\Request;

/**
 * Controlador de Historia Clínica (Paquete P7 / CU28 Consultar historia clínica)
 * Médico: pacientes de su agenda. Paciente: solo su propia historia (RN28 / RNF11).
 */
class HistoriaClinicaController extends Controller
{
    /**
     * [READ]: Médico elige un paciente de su agenda; el paciente va directo a su historia
     */
    public function index(Request $request)
    {
        if (AccesoClinico::rol() === 'Paciente') {
            $propio = AccesoClinico::pacienteActual();
            abort_if(!$propio, 403, 'Su usuario no está vinculado a un paciente.');
            return redirect()->route('historia.show', $propio);
        }

        if ($request->filled('paciente')) {
            return redirect()->route('historia.show', $request->input('paciente'));
        }

        $medico = AccesoClinico::medicoActual();
        abort_if(!$medico, 403, 'Su usuario no está vinculado a un perfil de médico.');
        $buscar = trim((string) $request->input('buscar'));
        $pacientes = Paciente::with('persona')
            ->whereHas('citas', fn ($q) => $q->where('medico_id', $medico->id))
            ->when($buscar, fn ($q) => $q->whereHas('persona', fn ($p) => $p
                ->where('ci', 'ilike', "%{$buscar}%")->orWhere('nombres', 'ilike', "%{$buscar}%")->orWhere('apellidos', 'ilike', "%{$buscar}%")))
            ->orderBy('id', 'desc')->paginate(15)->withQueryString();

        return view('historia.index', compact('pacientes', 'buscar'));
    }

    /**
     * [READ]: Historia clínica completa en orden cronológico (CU28)
     */
    public function show(Paciente $paciente)
    {
        AccesoClinico::autorizarPaciente($paciente);

        $paciente->load(['persona', 'alergias', 'medicamentos', 'antecedentes' => fn ($q) => $q->latest()]);

        // Consultas del paciente recorriendo sus citas atendidas, con receta y detalle
        $consultas = Consulta::with(['cita.medico.persona', 'cita.especialidad', 'receta.detalles.medicamento'])
            ->whereHas('cita', fn ($q) => $q->where('paciente_id', $paciente->id))
            ->get()
            ->sortByDesc(fn ($c) => $c->cita->fecha->toDateString() . ' ' . $c->cita->hora_inicio)
            ->values();

        BitacoraService::registrar('CONSULTAR_HISTORIA', 'pacientes', $paciente->id, "Consulta de la historia clínica del paciente {$paciente->codigo}.");

        return view('historia.show', compact('paciente', 'consultas'));
    }
}
