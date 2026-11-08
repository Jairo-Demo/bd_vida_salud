<?php

namespace App\Http\Controllers;

use App\Models\Paciente;
use App\Models\Persona;
use App\Models\User;
use App\Services\BitacoraService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * Controlador del Perfil del Paciente (Paquete P2 / CU09 Gestionar perfil del paciente)
 * El paciente edita sus datos de contacto y cambia su contraseña. CI, código y fecha de
 * nacimiento solo los corrige la recepción (CU06).
 */
class PerfilController extends Controller
{
    public function show()
    {
        [$user, $paciente] = $this->propio();
        return view('perfil.show', compact('user', 'paciente'));
    }

    /** Datos de contacto y de emergencia (persona + paciente en una transacción). */
    public function update(Request $request)
    {
        [$user, $paciente] = $this->propio();
        $persona = $paciente->persona;

        $data = $request->validate([
            'telefono' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s]{6,20}$/'],
            'correo' => ['required', 'email', 'max:150',
                Rule::unique('personas', 'correo')->ignore($persona->id),
                Rule::unique('users', 'email')->ignore($user->id)],
            'direccion' => 'nullable|string|max:255',
            'contacto_emerg_nombre' => 'nullable|string|max:150',
            'contacto_emerg_parentesco' => 'nullable|string|max:50',
            'contacto_emerg_telefono' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s]{6,20}$/'],
        ], [
            'correo.unique' => 'El correo ya está en uso por otra cuenta.',
            'correo.email' => 'El correo no tiene un formato válido.',
            'telefono.regex' => 'El teléfono solo puede tener números, espacios, + y -.',
            'contacto_emerg_telefono.regex' => 'El teléfono de emergencia solo puede tener números, espacios, + y -.',
        ]);

        DB::transaction(function () use ($user, $persona, $paciente, $data) {
            $persona->update(['telefono' => $data['telefono'] ?? null, 'correo' => $data['correo']]);
            $user->update(['email' => $data['correo']]);
            $paciente->update([
                'direccion' => $data['direccion'] ?? null,
                'contacto_emerg_nombre' => $data['contacto_emerg_nombre'] ?? null,
                'contacto_emerg_parentesco' => $data['contacto_emerg_parentesco'] ?? null,
                'contacto_emerg_telefono' => $data['contacto_emerg_telefono'] ?? null,
            ]);
        });
        BitacoraService::registrar('ACTUALIZAR_PERFIL', 'pacientes', $paciente->id, "El paciente {$paciente->codigo} actualizó sus datos de contacto.");

        return back()->with('success', 'Sus datos fueron actualizados correctamente.');
    }

    /** CU09 - Alterno A: cambio de contraseña verificando la actual. */
    public function password(Request $request)
    {
        [$user, $paciente] = $this->propio();

        $request->validate([
            'password_actual' => 'required|string',
            'password' => 'required|string|min:8|confirmed|different:password_actual',
        ], [
            'password.confirmed' => 'La confirmación de la nueva contraseña no coincide.',
            'password.min' => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'password.different' => 'La nueva contraseña debe ser distinta de la actual.',
        ]);

        if (!Hash::check($request->input('password_actual'), $user->password)) {
            return back()->withErrors(['password_actual' => 'La contraseña actual no es correcta.']);
        }

        $user->update(['password' => Hash::make($request->input('password'))]);
        BitacoraService::registrar('CAMBIAR_PASSWORD', 'users', $user->id, "El paciente {$paciente->codigo} cambió su contraseña.");

        return back()->with('success', 'Contraseña actualizada correctamente.');
    }

    /** @return array{0: User, 1: Paciente} */
    private function propio(): array
    {
        $user = Auth::user();
        $paciente = Paciente::with('persona')->where('persona_id', $user->persona_id)->first();
        abort_if(!$paciente, 403, 'Su usuario no está vinculado a un paciente.');
        return [$user, $paciente];
    }
}
