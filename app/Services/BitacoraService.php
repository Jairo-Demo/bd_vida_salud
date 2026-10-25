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
}
