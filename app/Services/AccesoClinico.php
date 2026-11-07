<?php

namespace App\Services;

use App\Models\Cita;
use App\Models\Medico;
use App\Models\Paciente;
use Illuminate\Support\Facades\Auth;

/**
 * Reglas de acceso a la información clínica (RN28 / RNF11) del paquete P7.
 * - El médico solo accede a pacientes de su agenda (con al menos una cita con él).
 * - El paciente solo accede a su propia información.
 */
class AccesoClinico
{
    public static function rol(): string
    {
        return Auth::user()->rol->nombre ?? '';
    }

    /** Perfil de médico del usuario autenticado (o null si no es médico). */
    public static function medicoActual(): ?Medico
    {
        return Medico::where('persona_id', Auth::user()->persona_id)->first();
    }

    /** Perfil de paciente del usuario autenticado (o null si no es paciente). */
    public static function pacienteActual(): ?Paciente
    {
        return Paciente::where('persona_id', Auth::user()->persona_id)->first();
    }

    /** ¿El paciente pertenece a la agenda del médico? (RN28) */
    public static function pacienteEnAgenda(Medico $medico, Paciente $paciente): bool
    {
        return Cita::where('medico_id', $medico->id)->where('paciente_id', $paciente->id)->exists();
    }

    /** Aborta con 403 si el usuario no puede ver los datos clínicos del paciente. */
    public static function autorizarPaciente(Paciente $paciente): void
    {
        $rol = self::rol();
        if ($rol === 'Medico') {
            $medico = self::medicoActual();
            abort_if(!$medico || !self::pacienteEnAgenda($medico, $paciente), 403,
                'Acceso denegado: el paciente no pertenece a su agenda (RN28).');
            return;
        }
        if ($rol === 'Paciente') {
            abort_if(self::pacienteActual()?->id !== $paciente->id, 403,
                'Acceso denegado: solo puede consultar su propia información clínica.');
            return;
        }
        abort(403, 'Acceso denegado: su rol no tiene acceso a la información clínica (RNF11).');
    }
}
