<?php

namespace App\Http\Controllers;

use App\Models\Especialidad;
use App\Models\Medico;
use App\Models\Persona;
use App\Services\BitacoraService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Controlador de Gestion de Medicos (Modulo P3 - PUDS / RF03 / CU11 - CU12)
 * Administra el personal facultativo y sus especialidades en estricto orden CRUD.
 */
class MedicoController extends Controller
{
    // =========================================================================
    // 1. CREATE (Crear y Registrar Médicos con Especialidades N:M)
    // =========================================================================

    /**
     * [CREATE - Vista]: Muestra el formulario con código sugerido y especialidades
     */
    public function create()
    {
        $ultimoId = Medico::max('id') ?? 0;
        $codigoSugerido = 'MED-' . str_pad($ultimoId + 1, 3, '0', STR_PAD_LEFT);
        $especialidades = Especialidad::where('estado', true)->orderBy('nombre')->get();

        return view('medicos.create', compact('codigoSugerido', 'especialidades'));
    }

    /**
     * [CREATE - Guardar]: Almacena el médico, su persona y vincula especialidades
     */
    public function store(Request $request)
    {
        $request->validate([
            'ci' => 'required|string|max:20|unique:personas,ci',
            'registro_profesional' => 'required|string|max:50|unique:medicos,registro_profesional',
            'nombres' => 'required|string|max:100',
            'apellidos' => 'required|string|max:100',
            'telefono' => 'nullable|string|max:20',
            'correo' => 'nullable|email|max:150',
            'especialidades' => 'required|array|min:1',
            'especialidades.*' => 'exists:especialidades,id',
        ], [
            'ci.unique' => 'Ya existe una persona registrada con este número de CI.',
            'registro_profesional.unique' => 'Este número de registro profesional ya está registrado en el sistema.',
            'especialidades.required' => 'Debe asignar al menos una especialidad al médico.',
        ]);

        DB::transaction(function () use ($request) {
            // A. Crear registro de Persona
            $persona = Persona::create([
                'ci' => trim($request->input('ci')),
                'nombres' => trim($request->input('nombres')),
                'apellidos' => trim($request->input('apellidos')),
                'telefono' => trim($request->input('telefono')),
                'correo' => trim($request->input('correo')),
                'estado' => true,
            ]);

            // B. Generar código único de médico
            $ultimoId = Medico::max('id') ?? 0;
            $codigo = 'MED-' . str_pad($ultimoId + 1, 3, '0', STR_PAD_LEFT);

            // C. Crear registro de Médico
            $medico = Medico::create([
                'persona_id' => $persona->id,
                'codigo' => $codigo,
                'registro_profesional' => trim($request->input('registro_profesional')),
                'estado' => true,
            ]);

            // D. Vincular especialidades (Asignación N:M)
            $pivotData = [];
            foreach ($request->input('especialidades') as $espId) {
                $pivotData[$espId] = [
                    'fecha_asignacion' => now()->toDateString(),
                    'estado' => true,
                ];
            }
            $medico->especialidades()->sync($pivotData);

            // E. Auditoría en bitácora
            BitacoraService::registrar(
                accion: 'CREAR_MEDICO',
                tabla: 'medicos',
                registroId: $medico->id,
                detalles: "Registro del médico: {$persona->nombre_completo} ({$codigo}, Reg: {$medico->registro_profesional})"
            );
        });

        return redirect()->route('medicos.index')->with('success', 'Médico registrado exitosamente.');
    }

    // =========================================================================
    // 2. READ (Lectura, Consultas, Búsqueda y Perfil Médico)
    // =========================================================================

    /**
     * [READ - Catálogo]: Lista de médicos con buscador por nombre, CI o especialidad
     */
    public function index(Request $request)
    {
        $buscar = trim($request->input('buscar'));
        $especialidadId = $request->input('especialidad_id');

        $query = Medico::with(['persona', 'especialidades']);

        // Búsqueda por texto (CI, nombres, apellidos o código)
        if (!empty($buscar)) {
            $query->where(function ($q) use ($buscar) {
                $q->where('codigo', 'ilike', "%{$buscar}%")
                  ->orWhere('registro_profesional', 'ilike', "%{$buscar}%")
                  ->orWhereHas('persona', function ($qp) use ($buscar) {
                      $qp->where('ci', 'ilike', "%{$buscar}%")
                         ->orWhere('nombres', 'ilike', "%{$buscar}%")
                         ->orWhere('apellidos', 'ilike', "%{$buscar}%");
                  });
            });
        }

        // Filtro por especialidad médica
        if (!empty($especialidadId)) {
            $query->whereHas('especialidades', function ($q) use ($especialidadId) {
                $q->where('especialidades.id', $especialidadId);
            });
        }

        $medicos = $query->orderBy('id', 'desc')->paginate(10)->withQueryString();
        $todasEspecialidades = Especialidad::where('estado', true)->orderBy('nombre')->get();

        return view('medicos.index', compact('medicos', 'buscar', 'especialidadId', 'todasEspecialidades'));
    }

    /**
     * [READ - Perfil y Agenda]: Muestra detalles, especialidades y horarios con fichas
     */
    public function show(Medico $medico)
    {
        $medico->load([
            'persona',
            'especialidades',
            'horarios.fichas',
            'citas' => function ($q) {
                $q->with(['paciente.persona', 'especialidad', 'estadoCita', 'ficha'])
                  ->orderBy('fecha', 'desc')
                  ->orderBy('hora_inicio', 'desc')
                  ->take(15);
            }
        ]);

        return view('medicos.show', compact('medico'));
    }

    // =========================================================================
    // 3. UPDATE (Modificar Datos y Especialidades del Médico)
    // =========================================================================

    /**
     * [UPDATE - Vista]: Muestra el formulario con datos actuales precargados
     */
    public function edit(Medico $medico)
    {
        $medico->load(['persona', 'especialidades']);
        $especialidades = Especialidad::where('estado', true)->orderBy('nombre')->get();

        return view('medicos.edit', compact('medico', 'especialidades'));
    }

    /**
     * [UPDATE - Guardar]: Actualiza la información personal y la asignación de especialidades
     */
    public function update(Request $request, Medico $medico)
    {
        $request->validate([
            'ci' => [
                'required',
                'string',
                'max:20',
                Rule::unique('personas', 'ci')->ignore($medico->persona_id),
            ],
            'registro_profesional' => [
                'required',
                'string',
                'max:50',
                Rule::unique('medicos', 'registro_profesional')->ignore($medico->id),
            ],
            'nombres' => 'required|string|max:100',
            'apellidos' => 'required|string|max:100',
            'telefono' => 'nullable|string|max:20',
            'correo' => 'nullable|email|max:150',
            'especialidades' => 'required|array|min:1',
            'especialidades.*' => 'exists:especialidades,id',
        ]);

        DB::transaction(function () use ($request, $medico) {
            // A. Actualizar datos de Persona
            $medico->persona->update([
                'ci' => trim($request->input('ci')),
                'nombres' => trim($request->input('nombres')),
                'apellidos' => trim($request->input('apellidos')),
                'telefono' => trim($request->input('telefono')),
                'correo' => trim($request->input('correo')),
            ]);

            // B. Actualizar registro profesional del Médico
            $medico->update([
                'registro_profesional' => trim($request->input('registro_profesional')),
            ]);

            // C. Sincronizar especialidades asociadas
            $pivotData = [];
            foreach ($request->input('especialidades') as $espId) {
                $pivotData[$espId] = [
                    'fecha_asignacion' => now()->toDateString(),
                    'estado' => true,
                ];
            }
            $medico->especialidades()->sync($pivotData);

            // D. Auditoría en bitácora
            BitacoraService::registrar(
                accion: 'ACTUALIZAR_MEDICO',
                tabla: 'medicos',
                registroId: $medico->id,
                detalles: "Actualización de datos del médico: {$medico->nombre_completo} ({$medico->codigo})"
            );
        });

        return redirect()->route('medicos.show', $medico)->with('success', 'Perfil del médico actualizado correctamente.');
    }

    // =========================================================================
    // 4. DELETE (Eliminación / Desactivación Lógica según Regla RN11)
    // =========================================================================

    /**
     * [DELETE - Estándar Resource]: Desactiva lógicamente si tiene citas (RN11) o elimina si es nuevo
     */
    public function destroy(Medico $medico)
    {
        $tieneCitas = $medico->citas()->exists();

        if ($tieneCitas) {
            // Desactivación lógica según RN11 para preservar histórico clínico
            $medico->update(['estado' => false]);
            $medico->persona->update(['estado' => false]);
            $medico->horarios()->update(['estado' => false]);

            BitacoraService::registrar(
                accion: 'DESACTIVAR_MEDICO_RN11',
                tabla: 'medicos',
                registroId: $medico->id,
                detalles: "Desactivación lógica del médico {$medico->nombre_completo} por registrar citas históricas (RN11)."
            );

            return redirect()->route('medicos.index')->with(
                'success',
                'El médico tiene citas asociadas: fue desactivado lógicamente para no perder el historial (RN11).'
            );
        }

        // Si no tiene citas asociadas, se puede eliminar físicamente
        DB::transaction(function () use ($medico) {
            $nombre = $medico->nombre_completo;
            $medicoId = $medico->id;

            $medico->especialidades()->detach();
            $medico->horarios()->delete();
            $medico->delete();
            $medico->persona->delete();

            BitacoraService::registrar(
                accion: 'ELIMINAR_MEDICO',
                tabla: 'medicos',
                registroId: $medicoId,
                detalles: "Eliminación física del médico sin citas: {$nombre}"
            );
        });

        return redirect()->route('medicos.index')->with('success', 'Médico eliminado correctamente.');
    }

    /**
     * [DELETE - Alternar Estado]: Activar / Desactivar manualmente desde el catálogo
     */
    public function toggleEstado(Medico $medico)
    {
        $nuevoEstado = !$medico->estado;

        DB::transaction(function () use ($medico, $nuevoEstado) {
            $medico->update(['estado' => $nuevoEstado]);
            $medico->persona->update(['estado' => $nuevoEstado]);
            $medico->horarios()->update(['estado' => $nuevoEstado]);

            BitacoraService::registrar(
                accion: 'CAMBIO_ESTADO_MEDICO',
                tabla: 'medicos',
                registroId: $medico->id,
                detalles: "Médico {$medico->nombre_completo} marcado como " . ($nuevoEstado ? 'ACTIVO' : 'INACTIVO')
            );
        });

        $mensaje = $nuevoEstado 
            ? 'El médico ha sido habilitado para atender citas.' 
            : 'El médico ha sido deshabilitado (no podrá recibir nuevas citas, RN04).';

        return back()->with('success', $mensaje);
    }
}
