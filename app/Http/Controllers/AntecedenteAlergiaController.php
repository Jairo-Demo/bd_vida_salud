<?php

namespace App\Http\Controllers;

use App\Models\Alergia;
use App\Models\Antecedente;
use App\Models\Consulta;
use App\Models\Medicamento;
use App\Models\Paciente;
use App\Services\AccesoClinico;
use App\Services\BitacoraService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Controlador de Alergias y Antecedentes (Paquete P7 / CU24)
 * El médico registra alergias (con severidad), antecedentes y medicación habitual de los pacientes de su agenda.
 */
class AntecedenteAlergiaController extends Controller
{
    /**
     * [READ]: Sin paciente: pacientes de la agenda del médico. Con paciente: sus alergias, antecedentes y medicación.
     */
    public function index(Request $request)
    {
        $medico = AccesoClinico::medicoActual();
        abort_if(!$medico, 403, 'Su usuario no está vinculado a un perfil de médico.');

        if (!$request->filled('paciente')) {
            $buscar = trim((string) $request->input('buscar'));
            $pacientes = Paciente::with('persona')
                ->whereHas('citas', fn ($q) => $q->where('medico_id', $medico->id))
                ->when($buscar, fn ($q) => $q->whereHas('persona', fn ($p) => $p
                    ->where('ci', 'ilike', "%{$buscar}%")->orWhere('nombres', 'ilike', "%{$buscar}%")->orWhere('apellidos', 'ilike', "%{$buscar}%")))
                ->orderBy('id', 'desc')->paginate(15)->withQueryString();
            return view('antecedentes.pacientes', compact('pacientes', 'buscar'));
        }

        $paciente = Paciente::with(['persona', 'alergias', 'medicamentos', 'antecedentes' => fn ($q) => $q->with('usuario')->latest()])
            ->findOrFail($request->input('paciente'));
        AccesoClinico::autorizarPaciente($paciente);

        $consulta = $request->filled('consulta') ? Consulta::whereHas('cita', fn ($q) => $q->where('paciente_id', $paciente->id))->find($request->input('consulta')) : null;
        $alergiasCatalogo = Alergia::where('estado', true)->whereNotIn('id', $paciente->alergias->pluck('id'))->orderBy('nombre')->get();
        $medicamentosCatalogo = Medicamento::where('estado', true)->whereNotIn('id', $paciente->medicamentos->pluck('id'))->orderBy('nombre_comercial')->get();

        return view('antecedentes.index', compact('paciente', 'consulta', 'alergiasCatalogo', 'medicamentosCatalogo'));
    }

    public function storeAlergia(Request $request, Paciente $paciente)
    {
        AccesoClinico::autorizarPaciente($paciente);
        $data = $request->validate([
            'alergia_id' => 'required|exists:alergias,id',
            'severidad' => 'required|in:Leve,Moderada,Grave',
            'reaccion' => 'nullable|string|max:255',
        ]);

        if ($paciente->alergias()->where('alergias.id', $data['alergia_id'])->exists()) {
            return back()->withErrors(['alergia_id' => 'La alergia ya está registrada para el paciente.']);
        }
        $alergia = Alergia::findOrFail($data['alergia_id']);
        $paciente->alergias()->attach($alergia->id, [
            'severidad' => $data['severidad'], 'reaccion' => $data['reaccion'] ?? null, 'fecha_registro' => today(),
        ]);
        BitacoraService::registrar('REGISTRAR_ALERGIA', 'paciente_alergia', $paciente->id, "Alergia {$alergia->nombre} ({$data['severidad']}) registrada al paciente {$paciente->codigo}.");

        return back()->with('success', "Alergia {$alergia->nombre} registrada.");
    }

    public function destroyAlergia(Paciente $paciente, Alergia $alergia)
    {
        AccesoClinico::autorizarPaciente($paciente);
        $paciente->alergias()->detach($alergia->id);
        BitacoraService::registrar('QUITAR_ALERGIA', 'paciente_alergia', $paciente->id, "Alergia {$alergia->nombre} retirada del paciente {$paciente->codigo} (registro por error).");

        return back()->with('success', "Alergia {$alergia->nombre} retirada.");
    }

    public function storeAntecedente(Request $request, Paciente $paciente)
    {
        AccesoClinico::autorizarPaciente($paciente);
        $data = $request->validate([
            'tipo' => 'required|in:Personal,Familiar,Quirurgico,Cronico,Otro',
            'descripcion' => 'required|string|min:3|max:1000',
            'consulta_id' => 'nullable|exists:consultas,id',
        ]);
        $consultaId = isset($data['consulta_id'])
            ? Consulta::whereHas('cita', fn ($q) => $q->where('paciente_id', $paciente->id))->whereKey($data['consulta_id'])->value('id')
            : null;

        $a = Antecedente::create([
            'paciente_id' => $paciente->id, 'consulta_id' => $consultaId, 'usuario_id' => Auth::id(),
            'tipo' => $data['tipo'], 'descripcion' => trim($data['descripcion']), 'fecha_registro' => today(),
        ]);
        BitacoraService::registrar('REGISTRAR_ANTECEDENTE', 'antecedentes', $a->id, "Antecedente {$a->tipo} registrado al paciente {$paciente->codigo}.");

        return back()->with('success', 'Antecedente registrado.');
    }

    public function destroyAntecedente(Antecedente $antecedente)
    {
        AccesoClinico::autorizarPaciente($antecedente->paciente);
        BitacoraService::registrar('QUITAR_ANTECEDENTE', 'antecedentes', $antecedente->id, "Antecedente {$antecedente->tipo} eliminado del paciente {$antecedente->paciente->codigo}: {$antecedente->descripcion}");
        $antecedente->delete();

        return back()->with('success', 'Antecedente eliminado.');
    }

    public function storeMedicamento(Request $request, Paciente $paciente)
    {
        AccesoClinico::autorizarPaciente($paciente);
        $data = $request->validate([
            'medicamento_id' => 'required|exists:medicamentos,id',
            'dosis' => 'nullable|string|max:100',
            'fecha_inicio' => 'nullable|date|before_or_equal:today',
        ]);
        if ($paciente->medicamentos()->where('medicamentos.id', $data['medicamento_id'])->exists()) {
            return back()->withErrors(['medicamento_id' => 'El medicamento ya está en la medicación habitual del paciente.']);
        }
        $m = Medicamento::findOrFail($data['medicamento_id']);
        $paciente->medicamentos()->attach($m->id, ['dosis' => $data['dosis'] ?? null, 'fecha_inicio' => $data['fecha_inicio'] ?? null]);
        BitacoraService::registrar('REGISTRAR_MEDICACION', 'paciente_medicamento', $paciente->id, "Medicación habitual {$m->nombre_comercial} registrada al paciente {$paciente->codigo}.");

        return back()->with('success', "Medicación {$m->nombre_comercial} registrada.");
    }

    public function destroyMedicamento(Paciente $paciente, Medicamento $medicamento)
    {
        AccesoClinico::autorizarPaciente($paciente);
        $paciente->medicamentos()->detach($medicamento->id);
        BitacoraService::registrar('QUITAR_MEDICACION', 'paciente_medicamento', $paciente->id, "Medicación {$medicamento->nombre_comercial} retirada del paciente {$paciente->codigo}.");

        return back()->with('success', "Medicación {$medicamento->nombre_comercial} retirada.");
    }
}
