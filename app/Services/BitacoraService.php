<?php

namespace App\Services;

use App\Models\Bitacora;
use Illuminate\Support\Facades\Auth;

class BitacoraService
{
    /**
     * Registra una accion en la bitacora de auditoria del sistema
     */
    public static function registrar(
        string $accion,
        ?string $tabla = null,
        ?int $registroId = null,
        ?string $detalles = null,
        string $canal = 'WEB'
    ): void {
        // Se obtiene el ID del usuario actualmente autenticado
        $userId = Auth::id();

        if (!$userId) {
            return;
        }

        // Se inserta el registro inmutable de auditoria
        Bitacora::create([
            'usuario_id' => $userId,
            'accion' => $accion,
            'tabla_afectada' => $tabla,
            'registro_id' => $registroId,
            'detalles' => $detalles,
            'ip' => request()->ip() ?? '127.0.0.1',
            'canal' => $canal,
            'fecha_hora' => now(),
        ]);
    }

    /**
     * Registro de acciones automáticas sin usuario autenticado (tarea programada CU31).
     * Se atribuyen al primer Administrador activo con canal SISTEMA.
     */
    public static function registrarSistema(string $accion, ?string $tabla = null, ?int $registroId = null, ?string $detalles = null): void
    {
        $adminId = \App\Models\User::whereHas('rol', fn ($q) => $q->where('nombre', 'Administrador'))
            ->orderBy('id')->value('id');
        if (!$adminId) {
            return;
        }

        Bitacora::create([
            'usuario_id' => $adminId,
            'accion' => $accion,
            'tabla_afectada' => $tabla,
            'registro_id' => $registroId,
            'detalles' => $detalles,
            'ip' => '127.0.0.1',
            'canal' => 'SISTEMA',
            'fecha_hora' => now(),
        ]);
    }
}
