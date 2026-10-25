<?php

namespace App\Http\Controllers;

use App\Models\Paciente;
use App\Models\Persona;
use App\Services\BitacoraService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Controlador de Gestion de Pacientes (Modulo P2 - PUDS / RF02)
 * Estructurado en estricto orden CRUD (Create, Read, Update, Delete)
 */
class PacienteController extends Controller
{
    // =========================================================================
    // 1. CREATE (Creación y Registro de Pacientes)
    // =========================================================================

    /**
     * [CREATE - Vista]: Muestra el formulario para registrar un nuevo paciente
     */
    public function create()
    {
        // Generar codigo correlativo sugerido (ej. PAC-003)
        $ultimoId = Paciente::max('id') ?? 0;
        $codigoSugerido = 'PAC-' . str_pad($ultimoId + 1, 3, '0', STR_PAD_LEFT);

        return view('pacientes.create', compact('codigoSugerido'));
    }

    /**
     * [CREATE - Guardar]: Almacena el paciente en la base de datos (Transacción atómica)
     */
    public function store(Request $request)
    {
        // Validación de datos según Reglas de Negocio (RN01)
        $request->validate([
            'ci' => 'required|string|max:20|unique:personas,ci',
            'nombres' => 'required|string|max:100',
            'apellidos' => 'required|string|max:100',
            'telefono' => 'nullable|string|max:20',
            'correo' => 'nullable|email|max:150',
            'fecha_nacimiento' => 'required|date|before_or_equal:today',
            'sexo' => 'required|in:M,F',
            'direccion' => 'nullable|string|max:255',
            'tipo_sangre' => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'otras_alergias' => 'nullable|string|max:500',
            'contacto_emerg_nombre' => 'nullable|string|max:100',
            'contacto_emerg_parentesco' => 'nullable|string|max:50',
            'contacto_emerg_telefono' => 'nullable|string|max:20',
        ], [
            'ci.unique' => 'Ya existe una persona registrada con este número de CI.',
            'fecha_nacimiento.before_or_equal' => 'La fecha de nacimiento no puede ser una fecha futura.',
            'sexo.required' => 'Seleccione el sexo del paciente.',
        ]);

        // Transacción atómica: se crea Persona y Paciente a la vez
        DB::transaction(function () use ($request) {
            // A. Crear registro en personas
            $persona = Persona::create([
                'ci' => trim($request->input('ci')),
                'nombres' => trim($request->input('nombres')),
                'apellidos' => trim($request->input('apellidos')),
                'telefono' => trim($request->input('telefono')),
                'correo' => trim($request->input('correo')),
                'estado' => true,
            ]);

            // B. Generar código correlativo único
            $ultimoId = Paciente::max('id') ?? 0;
            $codigo = 'PAC-' . str_pad($ultimoId + 1, 3, '0', STR_PAD_LEFT);

            // C. Crear registro en pacientes vinculado a la persona
            $paciente = Paciente::create([
                'persona_id' => $persona->id,
                'codigo' => $codigo,
                'fecha_nacimiento' => $request->input('fecha_nacimiento'),
                'sexo' => $request->input('sexo'),
                'direccion' => trim($request->input('direccion')),
                'tipo_sangre' => $request->input('tipo_sangre'),
                'otras_alergias' => trim($request->input('otras_alergias')),
                'contacto_emerg_nombre' => trim($request->input('contacto_emerg_nombre')),
                'contacto_emerg_parentesco' => trim($request->input('contacto_emerg_parentesco')),
                'contacto_emerg_telefono' => trim($request->input('contacto_emerg_telefono')),
                'estado' => true,
            ]);

            // D. Auditoría inmutable en bitácora
            BitacoraService::registrar(
                accion: 'CREAR_PACIENTE',
                tabla: 'pacientes',
                registroId: $paciente->id,
                detalles: "Registro de nuevo paciente: {$persona->nombre_completo} (Código: {$codigo}, CI: {$persona->ci})"
            );
        });

        return redirect()->route('pacientes.index')->with('success', 'Paciente registrado exitosamente.');
    }

    // =========================================================================
    // 2. READ (Lectura, Consultas, Búsqueda y Expediente)
    // =========================================================================

    /**
     * [READ - Catálogo y Búsqueda]: Lista pacientes con buscador por CI/nombre (CU07)
     */
    public function index(Request $request)
    {
        $buscar = trim($request->input('buscar'));
        $estado = $request->input('estado');

        $query = Paciente::with('persona');

        // Búsqueda por CI, nombres o apellidos
        if (!empty($buscar)) {
            $query->where(function ($q) use ($buscar) {
                $q->where('codigo', 'ilike', "%{$buscar}%")
                  ->orWhereHas('persona', function ($qp) use ($buscar) {
                      $qp->where('ci', 'ilike', "%{$buscar}%")
                         ->orWhere('nombres', 'ilike', "%{$buscar}%")
                         ->orWhere('apellidos', 'ilike', "%{$buscar}%");
                  });
            });
        }

        // Filtro por estado activo / inactivo
        if ($estado !== null && $estado !== '') {
            $query->where('estado', $estado === '1');
        }

        $pacientes = $query->orderBy('id', 'desc')->paginate(10)->withQueryString();

        return view('pacientes.index', compact('pacientes', 'buscar', 'estado'));
    }

    /**
     * [READ - Expediente Clínico]: Consulta el expediente e historial de citas (RF04 / RF12)
     */
    public function show(Paciente $paciente)
    {
        // Carga datos personales e historial cronológico de citas del paciente
        $paciente->load([
            'persona',
            'citas' => function ($q) {
                $q->with(['medico.persona', 'especialidad', 'estadoCita', 'ficha'])
                  ->orderBy('fecha', 'desc')
                  ->orderBy('hora_inicio', 'desc');
            }
        ]);

        return view('pacientes.show', compact('paciente'));
    }

    // =========================================================================
    // 3. UPDATE (Modificación y Actualización de Expedientes)
    // =========================================================================

    /**
     * [UPDATE - Vista]: Muestra el formulario con los datos cargados para edición
     */
    public function edit(Paciente $paciente)
    {
        $paciente->load('persona');
        return view('pacientes.edit', compact('paciente'));
    }

    /**
     * [UPDATE - Guardar]: Actualiza los datos demográficos y médicos del paciente
     */
    public function update(Request $request, Paciente $paciente)
    {
        // Validación permitiendo conservar el CI actual del paciente
        $request->validate([
            'ci' => [
                'required',
                'string',
                'max:20',
                Rule::unique('personas', 'ci')->ignore($paciente->persona_id),
            ],
            'nombres' => 'required|string|max:100',
            'apellidos' => 'required|string|max:100',
            'telefono' => 'nullable|string|max:20',
            'correo' => 'nullable|email|max:150',
            'fecha_nacimiento' => 'required|date|before_or_equal:today',
            'sexo' => 'required|in:M,F',
            'direccion' => 'nullable|string|max:255',
            'tipo_sangre' => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'otras_alergias' => 'nullable|string|max:500',
            'contacto_emerg_nombre' => 'nullable|string|max:100',
            'contacto_emerg_parentesco' => 'nullable|string|max:50',
            'contacto_emerg_telefono' => 'nullable|string|max:20',
        ]);

        DB::transaction(function () use ($request, $paciente) {
            // A. Actualizar datos en personas
            $paciente->persona->update([
                'ci' => trim($request->input('ci')),
                'nombres' => trim($request->input('nombres')),
                'apellidos' => trim($request->input('apellidos')),
                'telefono' => trim($request->input('telefono')),
                'correo' => trim($request->input('correo')),
            ]);

            // B. Actualizar datos en pacientes
            $paciente->update([
                'fecha_nacimiento' => $request->input('fecha_nacimiento'),
                'sexo' => $request->input('sexo'),
                'direccion' => trim($request->input('direccion')),
                'tipo_sangre' => $request->input('tipo_sangre'),
                'otras_alergias' => trim($request->input('otras_alergias')),
                'contacto_emerg_nombre' => trim($request->input('contacto_emerg_nombre')),
                'contacto_emerg_parentesco' => trim($request->input('contacto_emerg_parentesco')),
                'contacto_emerg_telefono' => trim($request->input('contacto_emerg_telefono')),
            ]);

            // C. Auditoría en bitácora
            BitacoraService::registrar(
                accion: 'ACTUALIZAR_PACIENTE',
                tabla: 'pacientes',
                registroId: $paciente->id,
                detalles: "Actualización de expediente del paciente: {$paciente->nombre_completo} ({$paciente->codigo})"
            );
        });

        return redirect()->route('pacientes.show', $paciente)->with('success', 'Expediente del paciente actualizado correctamente.');
    }

    // =========================================================================
    // 4. DELETE (Eliminación / Desactivación Lógica según Regla RN11)
    // =========================================================================

    /**
     * [DELETE - Estándar Resource]: Elimina o Desactiva lógicamente según RN11
     * RN11: Si tiene citas históricas, NO se borra físicamente; se desactiva.
     */
    public function destroy(Paciente $paciente)
    {
        // Verificar si tiene citas asociadas
        $tieneCitas = $paciente->citas()->exists();

        if ($tieneCitas) {
            // Desactivación lógica obligatoria por regla RN11 para no perder historial
            $paciente->update(['estado' => false]);
            $paciente->persona->update(['estado' => false]);

            BitacoraService::registrar(
                accion: 'DESACTIVAR_PACIENTE_RN11',
                tabla: 'pacientes',
                registroId: $paciente->id,
                detalles: "Desactivación lógica del paciente {$paciente->nombre_completo} para conservar su historial de citas (RN11)."
            );

            return redirect()->route('pacientes.index')->with(
                'success',
                'El paciente tiene citas asociadas: fue desactivado lógicamente para preservar su historial clínico (RN11).'
            );
        }

        // Si NO tiene citas asociadas, se puede eliminar físicamente
        DB::transaction(function () use ($paciente) {
            $nombre = $paciente->nombre_completo;
            $pacienteId = $paciente->id;

            $paciente->delete();
            $paciente->persona->delete();

            BitacoraService::registrar(
                accion: 'ELIMINAR_PACIENTE',
                tabla: 'pacientes',
                registroId: $pacienteId,
                detalles: "Eliminación física del paciente sin citas asociadas: {$nombre}"
            );
        });

        return redirect()->route('pacientes.index')->with('success', 'Paciente eliminado correctamente.');
    }

    /**
     * [DELETE - Alternar Estado]: Permite activar o desactivar manualmente al paciente
     */
    public function toggleEstado(Paciente $paciente)
    {
        $nuevoEstado = !$paciente->estado;

        DB::transaction(function () use ($paciente, $nuevoEstado) {
            $paciente->update(['estado' => $nuevoEstado]);
            $paciente->persona->update(['estado' => $nuevoEstado]);

            BitacoraService::registrar(
                accion: 'CAMBIO_ESTADO_PACIENTE',
                tabla: 'pacientes',
                registroId: $paciente->id,
                detalles: "El paciente {$paciente->nombre_completo} fue " . ($nuevoEstado ? 'ACTIVADO' : 'DESACTIVADO')
            );
        });

        $mensaje = $nuevoEstado 
            ? 'El paciente ha sido activado correctamente.' 
            : 'El paciente ha sido desactivado (su historial clínico permanece intacto).';

        return back()->with('success', $mensaje);
    }
}
