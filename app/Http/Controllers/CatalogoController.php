<?php

namespace App\Http\Controllers;

use App\Models\Alergia;
use App\Models\Medicamento;
use App\Services\BitacoraService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Controlador de Catálogos Clínicos (Paquete P7 / CU27 Gestionar catálogos de medicamentos y alergias)
 * Un medicamento inactivo no puede recetarse, pero se conserva en las recetas anteriores.
 */
class CatalogoController extends Controller
{
    /**
     * [READ]: Catálogo de medicamentos o alergias con búsqueda y estado
     */
    public function index(Request $request)
    {
        $tab = $request->input('tab') === 'alergias' ? 'alergias' : 'medicamentos';
        $buscar = trim((string) $request->input('buscar'));

        if ($tab === 'medicamentos') {
            $items = Medicamento::when($buscar, fn ($q) => $q->where(fn ($w) => $w
                    ->where('nombre_comercial', 'ilike', "%{$buscar}%")
                    ->orWhere('principio_activo', 'ilike', "%{$buscar}%")))
                ->orderBy('nombre_comercial')->paginate(15)->withQueryString();
            $editar = $request->filled('editar') ? Medicamento::find($request->input('editar')) : null;
        } else {
            $items = Alergia::when($buscar, fn ($q) => $q->where('nombre', 'ilike', "%{$buscar}%"))
                ->orderBy('nombre')->paginate(15)->withQueryString();
            $editar = $request->filled('editar') ? Alergia::find($request->input('editar')) : null;
        }

        return view('catalogos.index', compact('tab', 'buscar', 'items', 'editar'));
    }

    // ----------------------------- Medicamentos -----------------------------

    public function storeMedicamento(Request $request)
    {
        $data = $this->validarMedicamento($request);
        $m = Medicamento::create($data + ['estado' => true]);
        BitacoraService::registrar('CREAR_MEDICAMENTO', 'medicamentos', $m->id, "Medicamento {$m->nombre_comercial} ({$m->presentacion}) agregado al catálogo.");

        return redirect()->route('catalogos.index', ['tab' => 'medicamentos'])->with('success', "Medicamento {$m->nombre_comercial} registrado.");
    }

    public function updateMedicamento(Request $request, Medicamento $medicamento)
    {
        $data = $this->validarMedicamento($request, $medicamento->id);
        $medicamento->update($data);
        BitacoraService::registrar('EDITAR_MEDICAMENTO', 'medicamentos', $medicamento->id, "Medicamento {$medicamento->nombre_comercial} modificado.");

        return redirect()->route('catalogos.index', ['tab' => 'medicamentos'])->with('success', "Medicamento {$medicamento->nombre_comercial} actualizado.");
    }

    public function toggleMedicamento(Medicamento $medicamento)
    {
        $medicamento->update(['estado' => !$medicamento->estado]);
        $txt = $medicamento->estado ? 'activado' : 'desactivado (no podrá recetarse)';
        BitacoraService::registrar('ESTADO_MEDICAMENTO', 'medicamentos', $medicamento->id, "Medicamento {$medicamento->nombre_comercial} {$txt}.");

        return back()->with('success', "Medicamento {$medicamento->nombre_comercial} {$txt}.");
    }

    private function validarMedicamento(Request $request, ?int $id = null): array
    {
        $data = $request->validate([
            'nombre_comercial' => 'required|string|max:150',
            'principio_activo' => 'required|string|max:150',
            'presentacion' => 'required|string|max:100',
        ]);
        $data = array_map('trim', $data);

        $duplicado = Medicamento::whereRaw('LOWER(nombre_comercial) = ?', [mb_strtolower($data['nombre_comercial'])])
            ->whereRaw('LOWER(presentacion) = ?', [mb_strtolower($data['presentacion'])])
            ->when($id, fn ($q) => $q->where('id', '!=', $id))
            ->exists();
        if ($duplicado) {
            back()->withInput()->withErrors(['nombre_comercial' => 'Ya existe un medicamento con el mismo nombre comercial y presentación.'])->throwResponse();
        }
        return $data;
    }

    // ------------------------------- Alergias -------------------------------

    public function storeAlergia(Request $request)
    {
        $data = $this->validarAlergia($request);
        $a = Alergia::create($data + ['estado' => true]);
        BitacoraService::registrar('CREAR_ALERGIA', 'alergias', $a->id, "Alergia {$a->nombre} agregada al catálogo.");

        return redirect()->route('catalogos.index', ['tab' => 'alergias'])->with('success', "Alergia {$a->nombre} registrada.");
    }

    public function updateAlergia(Request $request, Alergia $alergia)
    {
        $data = $this->validarAlergia($request, $alergia->id);
        $alergia->update($data);
        BitacoraService::registrar('EDITAR_ALERGIA', 'alergias', $alergia->id, "Alergia {$alergia->nombre} modificada.");

        return redirect()->route('catalogos.index', ['tab' => 'alergias'])->with('success', "Alergia {$alergia->nombre} actualizada.");
    }

    public function toggleAlergia(Alergia $alergia)
    {
        $alergia->update(['estado' => !$alergia->estado]);
        $txt = $alergia->estado ? 'activada' : 'desactivada';
        BitacoraService::registrar('ESTADO_ALERGIA', 'alergias', $alergia->id, "Alergia {$alergia->nombre} {$txt}.");

        return back()->with('success', "Alergia {$alergia->nombre} {$txt}.");
    }

    private function validarAlergia(Request $request, ?int $id = null): array
    {
        $data = $request->validate(['nombre' => 'required|string|max:150']);
        $data['nombre'] = trim($data['nombre']);

        $duplicado = Alergia::whereRaw('LOWER(nombre) = ?', [mb_strtolower($data['nombre'])])
            ->when($id, fn ($q) => $q->where('id', '!=', $id))->exists();
        if ($duplicado) {
            back()->withInput()->withErrors(['nombre' => 'Ya existe una alergia con ese nombre.'])->throwResponse();
        }
        return $data;
    }
}
