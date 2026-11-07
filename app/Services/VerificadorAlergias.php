<?php

namespace App\Services;

use App\Models\Medicamento;
use App\Models\Paciente;
use Illuminate\Support\Str;

/**
 * CU26 Verificar alergias (incluido por CU25 Emitir receta).
 * Compara de forma normalizada (sin tildes ni mayúsculas) cada alergia del paciente con el
 * principio activo y el nombre comercial del medicamento. La misma regla existe en la base
 * de datos como fn_paciente_alergico (Word Cap. 6.2.3.3).
 */
class VerificadorAlergias
{
    /**
     * @return array<int, array{alergia:string, severidad:string, reaccion:?string, bloquea:bool}>
     */
    public static function verificar(Paciente $paciente, Medicamento $medicamento): array
    {
        $principio = self::normalizar($medicamento->principio_activo);
        $comercial = self::normalizar($medicamento->nombre_comercial);
        $alertas = [];

        foreach ($paciente->alergias as $alergia) {
            $nombre = self::normalizar($alergia->nombre);
            if ($nombre === '') {
                continue;
            }
            $coincide = str_contains($principio, $nombre) || str_contains($comercial, $nombre)
                || ($principio !== '' && str_contains($nombre, $principio));

            if ($coincide) {
                $severidad = $alergia->pivot->severidad;
                $alertas[] = [
                    'alergia' => $alergia->nombre,
                    'severidad' => $severidad,
                    'reaccion' => $alergia->pivot->reaccion,
                    'bloquea' => $severidad === 'Grave', // CU26 - alterno A: alergia grave bloquea el medicamento
                ];
            }
        }
        return $alertas;
    }

    public static function normalizar(?string $texto): string
    {
        return trim(Str::lower(Str::ascii((string) $texto)));
    }
}
