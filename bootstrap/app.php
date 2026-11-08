<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Alias para proteccion de rutas por rol
        $middleware->alias([
            'rol' => \App\Http\Middleware\CheckRole::class,
            'permiso' => \App\Http\Middleware\CheckPermiso::class, // CU04: permisos asignados al rol
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
