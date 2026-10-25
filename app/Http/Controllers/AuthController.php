<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\BitacoraService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Muestra la vista del formulario de login
     */
    public function showLoginForm()
    {
        // Si ya inicio sesion, enviar al dashboard
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /**
     * Procesa la autenticacion del usuario
     */
    public function login(Request $request)
    {
        // 1. Validar campos del formulario
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ], [
            'login.required' => 'Ingrese su usuario o correo electronico.',
            'password.required' => 'Ingrese su contrasena.',
        ]);

        $loginInput = trim($request->input('login'));
        $password = $request->input('password');

        // 2. Buscar usuario por username o por email
        $user = User::where('username', $loginInput)
            ->orWhere('email', $loginInput)
            ->first();

        // 3. Verificar usuario y clave hash
        if (!$user || !Hash::check($password, $user->password)) {
            return back()->withInput($request->only('login'))->withErrors([
                'login' => 'Credenciales invalidas. Verifique su usuario y contrasena.',
            ]);
        }

        // 4. Verificar si la cuenta esta activa
        if (!$user->estado) {
            return back()->withInput($request->only('login'))->withErrors([
                'login' => 'Su cuenta se encuentra inactiva. Comuniquese con el Administrador.',
            ]);
        }

        // 5. Iniciar sesion formalmente
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        // 6. Actualizar timestamp de ultimo acceso
        $user->update(['ultimo_acceso' => now()]);

        // 7. Registrar auditoria de LOGIN en bitacora
        BitacoraService::registrar(
            accion: 'LOGIN',
            tabla: 'users',
            registroId: $user->id,
            detalles: 'Inicio de sesion exitoso con rol: ' . ($user->rol->nombre ?? 'Sin rol')
        );

        // 8. Redirigir al dashboard general
        return redirect()->intended(route('dashboard'));
    }

    /**
     * Cierra la sesion del usuario actual
     */
    public function logout(Request $request)
    {
        // Registrar salida en la bitacora antes de destruir sesion
        if (Auth::check()) {
            BitacoraService::registrar(
                accion: 'LOGOUT',
                tabla: 'users',
                registroId: Auth::id(),
                detalles: 'Cierre de sesion voluntario'
            );
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Sesion cerrada exitosamente.');
    }
}
