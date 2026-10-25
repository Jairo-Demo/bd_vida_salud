<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Verifica que el usuario autenticado tenga uno de los roles permitidos
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // 1. Verificar si ha iniciado sesion
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Debe iniciar sesion para acceder.');
        }

        $user = Auth::user();

        // 2. Verificar que la cuenta este activa
        if (!$user->estado) {
            Auth::logout();
            return redirect()->route('login')->with('error', 'Su cuenta se encuentra inactiva. Contacte al administrador.');
        }

        // 3. Validar si el rol del usuario esta entre los permitidos para la ruta
        $rolUsuario = $user->rol ? $user->rol->nombre : null;

        if (!in_array($rolUsuario, $roles)) {
            abort(403, 'Acceso denegado: Su perfil no cuenta con permisos para este modulo.');
        }

        return $next($request);
    }
}
