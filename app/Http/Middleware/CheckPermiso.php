<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifica que el rol del usuario tenga el permiso indicado (matriz de Roles y Permisos - CU04).
 * Uso en rutas: ->middleware('permiso:CITAS_CANCELAR')
 */
class CheckPermiso
{
    public function handle(Request $request, Closure $next, string $codigo): Response
    {
        $user = Auth::user();
        if (!$user || !$user->tienePermiso($codigo)) {
            abort(403, "Acceso denegado: su rol no tiene el permiso {$codigo}.");
        }
        return $next($request);
    }
}
