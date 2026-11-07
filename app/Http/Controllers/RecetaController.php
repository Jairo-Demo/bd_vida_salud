<?php

namespace App\Http\Controllers;

use App\Models\Consulta;
use App\Models\DetalleReceta;
use App\Models\Medicamento;
use App\Models\Receta;
use App\Services\AccesoClinico;
use App\Services\BitacoraService;
use App\Services\VerificadorAlergias;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Controlador de Recetas (Paquete P7 / CU25 Emitir receta, incluye CU26 Verificar alergias)
 * Una consulta tiene como máximo una receta (RN23) con al menos un medicamento.
 */
class RecetaController extends Controller
{
    /**
     * [READ]: Recetas emitidas por el médico
     */
    public function index()
    {
        $medico = AccesoClinico::medicoActual();
        abort_if(!$medico, 403, 'Su usuario no está vinculado a un perfil de médico.');

        $recetas = Receta::with(['consulta.cita.paciente.persona'])->withCount('detalles')
            ->whereHas('consulta.cita', fn ($q) => $q->where('medico_id', $medico->id))
            ->latest()->paginate(15);

        $sinReceta = Consulta::with('cita.paciente.persona')
            ->whereHas('cita', fn ($q) => $q->where('medico_id', $medico->id))
            ->doesntHave('receta')->latest()->limit(10)->get();

        return view('recetas.index', compact('recetas', 'sinReceta'));
    }

    /**
     * [CREATE - Vista]: Formulario de receta para una consulta (CU25)
     */
    public function create(Request $request)
    {
        $consulta = $this->consultaDelMedico($request->input('consulta'));
        if ($consulta->receta) {
            return redirect()->route('recetas.show', $consulta->receta)->with('error', 'La consulta ya tiene una receta (RN23).');
        }
        $medicamentos = Medicamento::where('estado', true)->orderBy('nombre_comercial')->get();

        return view('recetas.create', compact('consulta', 'medicamentos'));
    }

    /**
     * [API - CU26]: Verifica las alergias del paciente para un medicamento (consulta en vivo desde el formulario)
     */
    public function verificarAlergia(Request $request)
    {
        $request->validate(['consulta' => 'required|integer', 'medicamento' => 'required|exists:medicamentos,id']);
        $consulta = $this->consultaDelMedico($request->input('consulta'));
        $medicamento = Medicamento::findOrFail($request->input('medicamento'));

        return response()->json([
            'medicamento' => $medicamento->nombre_comercial,
            'activo' => (bool) $medicamento->estado,
            'alertas' => VerificadorAlergias::verificar($consulta->cita->paciente, $medicamento),
        ]);
    }

    /**
     * [CREATE - Guardar]: Valida medicamentos y alergias, genera el código y guarda receta + detalle (CU25)
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'consulta_id' => 'required|integer',
            'observaciones' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.medicamento_id' => 'required|distinct|exists:medicamentos,id',
            'items.*.dosis' => 'required|string|max:100',
            'items.*.frecuencia' => 'required|string|max:100',
            'items.*.duracion_tratamiento' => 'nullable|string|max:100',
            'items.*.cantidad' => 'required|integer|min:1|max:999',
            'items.*.indicaciones' => 'nullable|string|max:500',
            'items.*.confirmar_alergia' => 'nullable|boolean',
        ], [
            'items.required' => 'La receta debe tener al menos un medicamento.',
            'items.min' => 'La receta debe tener al menos un medicamento.',
            'items.*.medicamento_id.distinct' => 'Un medicamento está repetido en la receta.',
            'items.*.dosis.required' => 'Indique la dosis de cada medicamento.',
            'items.*.frecuencia.required' => 'Indique la frecuencia de cada medicamento.',
        ]);

        $consulta = $this->consultaDelMedico($data['consulta_id']);
        if ($consulta->receta) {
            return redirect()->route('recetas.show', $consulta->receta)->with('error', 'La consulta ya tiene una receta (RN23).');
        }
        $paciente = $consulta->cita->paciente;

        // CU26: verificación de alergias de cada medicamento
        $errores = [];
        $justificaciones = [];
        foreach ($data['items'] as $i => $item) {
            $m = Medicamento::find($item['medicamento_id']);
            if (!$m->estado) {
                $errores["items.$i.medicamento_id"] = "{$m->nombre_comercial} está inactivo en el catálogo y no puede recetarse.";
                continue;
            }
            foreach (VerificadorAlergias::verificar($paciente, $m) as $al) {
                if ($al['bloquea']) {
                    $errores["items.$i.medicamento_id"] = "Alergia GRAVE a {$al['alergia']}: {$m->nombre_comercial} no puede recetarse; reemplácelo.";
                } elseif (empty($item['confirmar_alergia'])) {
                    $errores["items.$i.medicamento_id"] = "Alerta: el paciente es alérgico a {$al['alergia']} ({$al['severidad']}). Retire {$m->nombre_comercial} o confirme bajo su responsabilidad.";
                } else {
                    $justificaciones[] = "{$m->nombre_comercial} recetado pese a alergia a {$al['alergia']} ({$al['severidad']})";
                }
            }
        }
        if ($justificaciones && blank($data['observaciones'] ?? null)) {
            $errores['observaciones'] = 'Escriba en observaciones la justificación para recetar un medicamento con alergia registrada.';
        }
        if ($errores) {
            return back()->withInput()->withErrors($errores);
        }

        $receta = DB::transaction(function () use ($consulta, $data, $justificaciones) {
            $siguiente = (int) DB::table('recetas')->max('id') + 1;
            $obs = trim($data['observaciones'] ?? '');
            if ($justificaciones) {
                $obs = trim($obs . "\n[Verificación de alergias] " . implode('; ', $justificaciones) . '.');
            }

            $receta = Receta::create([
                'consulta_id' => $consulta->id,
                'codigo' => 'RX-' . str_pad($siguiente, 4, '0', STR_PAD_LEFT),
                'fecha_emision' => today(),
                'observaciones' => $obs ?: null,
            ]);

            foreach ($data['items'] as $item) {
                DetalleReceta::create([
                    'receta_id' => $receta->id,
                    'medicamento_id' => $item['medicamento_id'],
                    'dosis' => trim($item['dosis']),
                    'frecuencia' => trim($item['frecuencia']),
                    'duracion_tratamiento' => $item['duracion_tratamiento'] ?? null,
                    'cantidad' => $item['cantidad'],
                    'indicaciones' => $item['indicaciones'] ?? null,
                ]);
            }

            BitacoraService::registrar(
                accion: 'EMITIR_RECETA',
                tabla: 'recetas',
                registroId: $receta->id,
                detalles: "Receta {$receta->codigo} emitida para la cita {$consulta->cita->codigo} con " . count($data['items']) . ' medicamento(s).'
            );
            return $receta;
        });

        return redirect()->route('recetas.show', $receta)->with('success', "Receta {$receta->codigo} emitida. Puede imprimirla o guardarla en PDF.");
    }

    /**
     * [READ]: Receta lista para imprimir / guardar en PDF (médico autor o paciente dueño)
     */
    public function show(Receta $receta)
    {
        $receta->load(['detalles.medicamento', 'consulta.cita.paciente.persona', 'consulta.cita.medico.persona', 'consulta.cita.especialidad']);
        $cita = $receta->consulta->cita;
        AccesoClinico::autorizarPaciente($cita->paciente);
        if (AccesoClinico::rol() === 'Medico') {
            abort_if((int) $cita->medico_id !== (int) AccesoClinico::medicoActual()?->id, 403, 'La receta pertenece a otro médico.');
        }

        return view('recetas.show', compact('receta', 'cita'));
    }

    /** Consulta del médico autenticado (403 si es de otro médico). */
    private function consultaDelMedico($id): Consulta
    {
        $consulta = Consulta::with(['cita.paciente.persona', 'cita.paciente.alergias', 'receta'])->findOrFail($id);
        $medico = AccesoClinico::medicoActual();
        abort_if(!$medico || (int) $consulta->cita->medico_id !== (int) $medico->id, 403, 'La consulta pertenece a otro médico.');
        return $consulta;
    }
}
