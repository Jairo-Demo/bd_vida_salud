<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use App\Models\Persona;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * Controlador de Usuarios y Asignación de Roles (PUDS Ciclo 1 - Paquete 1)
 * Casos de Uso: CU03 (Gestionar usuarios) y CU04 (Asignar rol y permisos)
 * Estructura en orden estricto C-R-U-D con comentarios educativos.
 */
class UsuarioController extends Controller
{
    // =========================================================================
    // 1. CREATE (Registro de Nuevas Cuentas de Acceso - CU03 / CU04)
    // =========================================================================

    /**
     * [CREATE - Formulario]: Muestra la interfaz para registrar un nuevo usuario del personal
     */
    public function create()
    {
        $roles = Rol::where('estado', true)->get();
        return view('usuarios.create', compact('roles'));
    }

    /**
     * [CREATE - Almacenamiento]: Persiste Persona + User en transacción atómica y audita
     */
    public function store(Request $request)
    {
        $request->validate([
            'ci' => ['required', 'string', 'max:20', 'unique:personas,ci'],
            'nombres' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'email' => ['required', 'string', 'email', 'max:150', 'unique:personas,correo', 'unique:users,email'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:users,username'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'rol_id' => ['required', 'exists:roles,id'],
        ], [
            'ci.unique' => 'Ya existe una persona registrada con este número de CI.',
            'email.unique' => 'Este correo electrónico ya está en uso.',
            'username.unique' => 'Este nombre de usuario ya fue ocupado.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'rol_id.required' => 'Debe asignar un rol al usuario (CU04).',
        ]);

        $usuario = DB::transaction(function () use ($request) {
            // 1. Crear persona base
            $persona = Persona::create([
                'ci' => trim($request->ci),
                'nombres' => trim($request->nombres),
                'apellidos' => trim($request->apellidos),
                'telefono' => $request->telefono ? trim($request->telefono) : null,
                'correo' => strtolower(trim($request->email)),
                'estado' => true,
            ]);

            // 2. Crear cuenta de usuario asociada
            $user = User::create([
                'persona_id' => $persona->id,
                'rol_id' => $request->rol_id,
                'name' => "{$persona->nombres} {$persona->apellidos}",
                'username' => strtolower(trim($request->username)),
                'email' => strtolower(trim($request->email)),
                'password' => Hash::make($request->password),
                'estado' => true,
            ]);

            // 3. Auditoría en Bitácora (RN15 / CU05)
            Bitacora::registrar(
                accion: 'CREAR_USUARIO',
                tabla: 'users',
                registroId: $user->id,
                detalles: "Alta de usuario '{$user->username}' con rol '{$user->rol->nombre}' para la persona {$persona->nombre_completo} (CI: {$persona->ci})."
            );

            return $user;
        });

        return redirect()->route('usuarios.index')->with('success', "Usuario '{$usuario->username}' registrado y rol asignado con éxito.");
    }

    // =========================================================================
    // 2. READ (Listado y Consulta de Usuarios - CU03)
    // =========================================================================

    /**
     * [READ - Catálogo]: Lista las cuentas de usuario con búsqueda y filtro de rol
     */
    public function index(Request $request)
    {
        $buscar = trim($request->input('buscar'));
        $rolId = $request->input('rol_id');
        $estado = $request->input('estado');

        $query = User::with(['persona', 'rol'])->orderBy('id', 'asc');

        if (!empty($buscar)) {
            $query->where(function ($q) use ($buscar) {
                $q->where('username', 'ilike', "%{$buscar}%")
                  ->orWhere('name', 'ilike', "%{$buscar}%")
                  ->orWhere('email', 'ilike', "%{$buscar}%")
                  ->orWhereHas('persona', function ($qp) use ($buscar) {
                      $qp->where('ci', 'ilike', "%{$buscar}%");
                  });
            });
        }

        if (!empty($rolId)) {
            $query->where('rol_id', $rolId);
        }

        if ($estado !== null && $estado !== '') {
            $query->where('estado', $estado === '1');
        }

        $usuarios = $query->paginate(15)->withQueryString();
        $roles = Rol::where('estado', true)->get();

        return view('usuarios.index', compact('usuarios', 'roles', 'buscar', 'rolId', 'estado'));
    }

    /**
     * [READ - Detalle]: Muestra la información de la cuenta y rol del usuario
     */
    public function show(User $usuario)
    {
        $usuario->load(['persona', 'rol.permisos']);
        return view('usuarios.show', compact('usuario'));
    }

    // =========================================================================
    // 3. UPDATE (Modificación de Cuenta, Cambio de Rol y Contraseña - CU03 / CU04)
    // =========================================================================

    /**
     * [UPDATE - Formulario]: Formulario de edición de usuario y rol
     */
    public function edit(User $usuario)
    {
        $usuario->load(['persona', 'rol']);
        $roles = Rol::where('estado', true)->get();
        return view('usuarios.edit', compact('usuario', 'roles'));
    }

    /**
     * [UPDATE - Actualización]: Actualiza datos, rol y contraseña opcional
     */
    public function update(Request $request, User $usuario)
    {
        $persona = $usuario->persona;

        $request->validate([
            'ci' => ['required', 'string', 'max:20', Rule::unique('personas', 'ci')->ignore($persona->id)],
            'nombres' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'email' => ['required', 'string', 'email', 'max:150', Rule::unique('users', 'email')->ignore($usuario->id)],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('users', 'username')->ignore($usuario->id)],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
            'rol_id' => ['required', 'exists:roles,id'],
            'estado' => ['required', 'boolean'],
        ]);

        DB::transaction(function () use ($request, $usuario, $persona) {
            // Actualizar persona
            $persona->update([
                'ci' => trim($request->ci),
                'nombres' => trim($request->nombres),
                'apellidos' => trim($request->apellidos),
                'telefono' => $request->telefono ? trim($request->telefono) : null,
                'correo' => strtolower(trim($request->email)),
            ]);

            // Actualizar usuario
            $dataUser = [
                'name' => "{$persona->nombres} {$persona->apellidos}",
                'username' => strtolower(trim($request->username)),
                'email' => strtolower(trim($request->email)),
                'rol_id' => $request->rol_id,
                'estado' => (bool)$request->estado,
            ];

            if ($request->filled('password')) {
                $dataUser['password'] = Hash::make($request->password);
            }

            $usuario->update($dataUser);

            // Bitácora
            Bitacora::registrar(
                accion: 'ACTUALIZAR_USUARIO',
                tabla: 'users',
                registroId: $usuario->id,
                detalles: "Actualización de usuario '{$usuario->username}' (Rol: {$usuario->rol->nombre}, Estado: " . ($usuario->estado ? 'Activo' : 'Inactivo') . ")."
            );
        });

        return redirect()->route('usuarios.index')->with('success', "Usuario '{$usuario->username}' actualizado exitosamente.");
    }

    /**
     * [UPDATE - Estado Rápido]: Activar o desactivar cuenta de usuario
     */
    public function toggleEstado(User $usuario)
    {
        // Evitar que el usuario actual se deshabilite a sí mismo
        if ($usuario->id === Auth::id()) {
            return redirect()->back()->with('error', 'No puede desactivar su propia cuenta activa.');
        }

        $usuario->estado = !$usuario->estado;
        $usuario->save();

        Bitacora::registrar(
            accion: 'CAMBIAR_ESTADO_USUARIO',
            tabla: 'users',
            registroId: $usuario->id,
            detalles: "Cambio de estado del usuario '{$usuario->username}' a " . ($usuario->estado ? 'Activo' : 'Inactivo')
        );

        return redirect()->back()->with('success', "Estado del usuario '{$usuario->username}' actualizado.");
    }

    // =========================================================================
    // 4. DELETE (Eliminación / Desactivación Lógica - RN11)
    // =========================================================================

    /**
     * [DELETE]: Desactiva lógicamente el usuario para mantener la integridad
     */
    public function destroy(User $usuario)
    {
        if ($usuario->id === Auth::id()) {
            return redirect()->back()->with('error', 'No puede eliminar su propia cuenta activa.');
        }

        $usuario->estado = false;
        $usuario->save();

        Bitacora::registrar(
            accion: 'DESACTIVAR_USUARIO',
            tabla: 'users',
            registroId: $usuario->id,
            detalles: "Desactivación lógica de la cuenta '{$usuario->username}' (RN11)."
        );

        return redirect()->route('usuarios.index')->with('success', "Usuario '{$usuario->username}' desactivado correctamente.");
    }
}
