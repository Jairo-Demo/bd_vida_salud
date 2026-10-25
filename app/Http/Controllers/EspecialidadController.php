<?php

namespace App\Http\Controllers;

use App\Models\Especialidad;
use App\Services\BitacoraService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Controlador de Especialidades Medicas (Modulo P3 - PUDS / RF04 / CU10)
 * Administra el catalogo clinico en estricto orden CRUD.
 */
class EspecialidadController extends Controller
{
    // =========================================================================
    // 1. CREATE (Crear y Registrar Especialidad)
    // =========================================================================

    /**
     * [CREATE - Vista]: Muestra el formulario para registrar una nueva especialidad
     */
    public function create()
    {
        return view('especialidades.create');
    }

    /**
     * [CREATE - Guardar]: Almacena la especialidad con nombre normalizado único (RN)
     */
    public function store(Request $request)
    {
        // Normalizar nombre: ej. "Medicina General" -> "medicina_general"
        $normalizado = Str::slug($request->input('nombre'), '_');

        $request->merge(['nombre_normalizado' => $normalizado]);

        $request->validate([
            'nombre' => 'required|string|max:100',
            'nombre_normalizado' => 'required|string|max:100|unique:especialidades,nombre_normalizado',
            'descripcion' => 'nullable|string|max:255',
        ], [
            'nombre.required' => 'El nombre de la especialidad es obligatorio.',
            'nombre_normalizado.unique' => 'Ya existe una especialidad registrada con este nombre o similar.',
        ]);

        $especialidad = Especialidad::create([
            'nombre' => trim($request->input('nombre')),
            'nombre_normalizado' => $normalizado,
            'descripcion' => trim($request->input('descripcion')),
            'estado' => true,
        ]);

        BitacoraService::registrar(
            accion: 'CREAR_ESPECIALIDAD',
            tabla: 'especialidades',
            registroId: $especialidad->id,
            detalles: "Registro de nueva especialidad médica: {$especialidad->nombre}"
        );

        return redirect()->route('especialidades.index')->with('success', 'Especialidad registrada exitosamente.');
    }

    // =========================================================================
    // 2. READ (Leer, Consultar y Listar Especialidades)
    // =========================================================================

    /**
     * [READ - Catálogo]: Lista especialidades con buscador y conteo de médicos
     */
    public function index(Request $request)
    {
        $buscar = trim($request->input('buscar'));

        $query = Especialidad::withCount('medicos');

        if (!empty($buscar)) {
            $query->where('nombre', 'ilike', "%{$buscar}%")
                  ->orWhere('descripcion', 'ilike', "%{$buscar}%");
        }

        $especialidades = $query->orderBy('nombre', 'asc')->paginate(10)->withQueryString();

        return view('especialidades.index', compact('especialidades', 'buscar'));
    }

    /**
     * [READ - Detalle]: Muestra la información de la especialidad y sus médicos asociados
     */
    public function show(Especialidad $especialidade)
    {
        $especialidad = $especialidade;
        $especialidad->load(['medicos.persona']);

        return view('especialidades.show', compact('especialidad'));
    }

    // =========================================================================
    // 3. UPDATE (Modificar y Actualizar Especialidad)
    // =========================================================================

    /**
     * [UPDATE - Vista]: Muestra el formulario de edición
     */
    public function edit(Especialidad $especialidade)
    {
        $especialidad = $especialidade;
        return view('especialidades.edit', compact('especialidad'));
    }

    /**
     * [UPDATE - Guardar]: Actualiza los datos de la especialidad
     */
    public function update(Request $request, Especialidad $especialidade)
    {
        $especialidad = $especialidade;
        $normalizado = Str::slug($request->input('nombre'), '_');
        $request->merge(['nombre_normalizado' => $normalizado]);

        $request->validate([
            'nombre' => 'required|string|max:100',
            'nombre_normalizado' => [
                'required',
                'string',
                'max:100',
                Rule::unique('especialidades', 'nombre_normalizado')->ignore($especialidad->id),
            ],
            'descripcion' => 'nullable|string|max:255',
        ]);

        $especialidad->update([
            'nombre' => trim($request->input('nombre')),
            'nombre_normalizado' => $normalizado,
            'descripcion' => trim($request->input('descripcion')),
        ]);

        BitacoraService::registrar(
            accion: 'ACTUALIZAR_ESPECIALIDAD',
            tabla: 'especialidades',
            registroId: $especialidad->id,
            detalles: "Actualización de la especialidad: {$especialidad->nombre}"
        );

        return redirect()->route('especialidades.index')->with('success', 'Especialidad actualizada correctamente.');
    }

    // =========================================================================
    // 4. DELETE (Eliminar o Desactivar Lógicamente según Citas Asociadas)
    // =========================================================================

    /**
     * [DELETE - Estándar]: Elimina si no tiene citas asociadas o desactiva lógicamente
     */
    public function destroy(Especialidad $especialidade)
    {
        $especialidad = $especialidade;
        // Verificar si tiene citas médicas históricas asociadas
        $tieneCitas = $especialidad->citas()->exists();

        if ($tieneCitas) {
            $especialidad->update(['estado' => false]);

            BitacoraService::registrar(
                accion: 'DESACTIVAR_ESPECIALIDAD',
                tabla: 'especialidades',
                registroId: $especialidad->id,
                detalles: "Desactivación lógica de especialidad {$especialidad->nombre} por tener citas históricas asociadas."
            );

            return redirect()->route('especialidades.index')->with(
                'success',
                'La especialidad tiene citas registradas: fue desactivada lógicamente para preservar el historial.'
            );
        }

        $nombre = $especialidad->nombre;
        $espId = $especialidad->id;
        $especialidad->delete();

        BitacoraService::registrar(
            accion: 'ELIMINAR_ESPECIALIDAD',
            tabla: 'especialidades',
            registroId: $espId,
            detalles: "Eliminación física de la especialidad sin citas: {$nombre}"
        );

        return redirect()->route('especialidades.index')->with('success', 'Especialidad eliminada correctamente.');
    }

    /**
     * [DELETE - Alternar Estado]: Activar / Desactivar manualmente
     */
    public function toggleEstado(Especialidad $especialidade)
    {
        $especialidad = $especialidade;
        $nuevoEstado = !$especialidad->estado;
        $especialidad->update(['estado' => $nuevoEstado]);

        BitacoraService::registrar(
            accion: 'CAMBIO_ESTADO_ESPECIALIDAD',
            tabla: 'especialidades',
            registroId: $especialidad->id,
            detalles: "Especialidad {$especialidad->nombre} marcada como " . ($nuevoEstado ? 'ACTIVA' : 'INACTIVA')
        );

        return back()->with('success', 'Estado de la especialidad actualizado correctamente.');
    }
}
