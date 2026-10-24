<?php

namespace Database\Seeders;

use App\Models\Especialidad;
use App\Models\Ficha;
use App\Models\HorarioAtencion;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\Persona;
use App\Models\Rol;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatosInicialesSeeder extends Seeder
{
    public function run(): void
    {
        $rolAdmin = Rol::where('nombre', 'Administrador')->firstOrFail();
        $rolRecep = Rol::where('nombre', 'Recepcionista')->firstOrFail();
        $rolMed = Rol::where('nombre', 'Medico')->firstOrFail();
        $rolPac = Rol::where('nombre', 'Paciente')->firstOrFail();

        // -------------------------------------------------------------
        // 1. Usuario Administrador (Jhon Jairo Patzi Balvin)
        // -------------------------------------------------------------
        $pAdmin = Persona::updateOrCreate(
            ['ci' => '11325356'],
            [
                'nombres' => 'Jhon Jairo',
                'apellidos' => 'Patzi Balvin',
                'telefono' => '67891791',
                'correo' => '47896jhonpro@gmail.com',
                'estado' => true,
            ]
        );

        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'persona_id' => $pAdmin->id,
                'rol_id' => $rolAdmin->id,
                'name' => 'Jhon Jairo Patzi Balvin',
                'email' => '47896jhonpro@gmail.com',
                'password' => Hash::make('admin123'),
                'estado' => true,
            ]
        );

        // -------------------------------------------------------------
        // 2. Personal de Recepción
        // -------------------------------------------------------------
        $pRecep = Persona::updateOrCreate(
            ['ci' => '2000002'],
            [
                'nombres' => 'María',
                'apellidos' => 'López Méndez',
                'telefono' => '71122334',
                'correo' => 'recepcion@vidasalud.com',
                'estado' => true,
            ]
        );

        User::updateOrCreate(
            ['username' => 'recepcion'],
            [
                'persona_id' => $pRecep->id,
                'rol_id' => $rolRecep->id,
                'name' => 'María López Méndez',
                'email' => 'recepcion@vidasalud.com',
                'password' => Hash::make('recep123'),
                'estado' => true,
            ]
        );

        // -------------------------------------------------------------
        // 3. Médico 1: Dr. Alejandro Roca Melgar (Medicina General y Pediatría)
        // -------------------------------------------------------------
        $pMed1 = Persona::updateOrCreate(
            ['ci' => '3000003'],
            [
                'nombres' => 'Alejandro',
                'apellidos' => 'Roca Melgar',
                'telefono' => '72233445',
                'correo' => 'dr.roca@vidasalud.com',
                'estado' => true,
            ]
        );

        $med1 = Medico::updateOrCreate(
            ['persona_id' => $pMed1->id],
            [
                'codigo' => 'MED-001',
                'registro_profesional' => 'COLMED-8492',
                'estado' => true,
            ]
        );

        User::updateOrCreate(
            ['username' => 'dr.roca'],
            [
                'persona_id' => $pMed1->id,
                'rol_id' => $rolMed->id,
                'name' => 'Dr. Alejandro Roca Melgar',
                'email' => 'dr.roca@vidasalud.com',
                'password' => Hash::make('medico123'),
                'estado' => true,
            ]
        );

        // Asignar especialidades a Medico 1
        $espMedGeneral = Especialidad::where('nombre_normalizado', 'medicina_general')->first();
        $espPediatria = Especialidad::where('nombre_normalizado', 'pediatria')->first();
        $med1->especialidades()->syncWithoutDetaching([
            $espMedGeneral->id => ['fecha_asignacion' => now()->toDateString(), 'estado' => true],
            $espPediatria->id => ['fecha_asignacion' => now()->toDateString(), 'estado' => true],
        ]);

        // Horarios Medico 1: Lunes, Miércoles y Viernes de 08:00 a 12:00 (turnos de 30 min)
        foreach (['Lunes', 'Miercoles', 'Viernes'] as $dia) {
            $horario = HorarioAtencion::updateOrCreate(
                [
                    'medico_id' => $med1->id,
                    'dia_semana' => $dia,
                    'hora_inicio' => '08:00:00',
                ],
                [
                    'hora_fin' => '12:00:00',
                    'duracion_cita_min' => 30,
                    'estado' => true,
                ]
            );

            // Generar 8 fichas por cada bloque de 4 horas
            $this->generarFichasHorario($horario);
        }

        // -------------------------------------------------------------
        // 4. Médico 2: Dra. Patricia Vargas Castro (Ginecología y Obstetricia)
        // -------------------------------------------------------------
        $pMed2 = Persona::updateOrCreate(
            ['ci' => '4000004'],
            [
                'nombres' => 'Patricia',
                'apellidos' => 'Vargas Castro',
                'telefono' => '73344556',
                'correo' => 'dra.vargas@vidasalud.com',
                'estado' => true,
            ]
        );

        $med2 = Medico::updateOrCreate(
            ['persona_id' => $pMed2->id],
            [
                'codigo' => 'MED-002',
                'registro_profesional' => 'COLMED-9104',
                'estado' => true,
            ]
        );

        User::updateOrCreate(
            ['username' => 'dra.vargas'],
            [
                'persona_id' => $pMed2->id,
                'rol_id' => $rolMed->id,
                'name' => 'Dra. Patricia Vargas Castro',
                'email' => 'dra.vargas@vidasalud.com',
                'password' => Hash::make('medico123'),
                'estado' => true,
            ]
        );

        // Asignar especialidad Ginecología
        $espGineco = Especialidad::where('nombre_normalizado', 'ginecologia_y_obstetricia')->first();
        if ($espGineco) {
            $med2->especialidades()->syncWithoutDetaching([
                $espGineco->id => ['fecha_asignacion' => now()->toDateString(), 'estado' => true],
            ]);
        }

        // Horarios Medico 2: Martes y Jueves de 14:00 a 18:00 (turnos de 30 min)
        foreach (['Martes', 'Jueves'] as $dia) {
            $horario = HorarioAtencion::updateOrCreate(
                [
                    'medico_id' => $med2->id,
                    'dia_semana' => $dia,
                    'hora_inicio' => '14:00:00',
                ],
                [
                    'hora_fin' => '18:00:00',
                    'duracion_cita_min' => 30,
                    'estado' => true,
                ]
            );

            $this->generarFichasHorario($horario);
        }

        // -------------------------------------------------------------
        // 5. Pacientes Demo
        // -------------------------------------------------------------
        // Paciente 1
        $pPac1 = Persona::updateOrCreate(
            ['ci' => '5000005'],
            [
                'nombres' => 'Juan',
                'apellidos' => 'Mamani Condori',
                'telefono' => '74455667',
                'correo' => 'juan.paciente@gmail.com',
                'estado' => true,
            ]
        );

        Paciente::updateOrCreate(
            ['persona_id' => $pPac1->id],
            [
                'codigo' => 'PAC-001',
                'fecha_nacimiento' => '1990-05-15',
                'sexo' => 'M',
                'direccion' => 'Av. Busch #450, Santa Cruz',
                'tipo_sangre' => 'O+',
                'otras_alergias' => 'Ninguna conocida',
                'contacto_emerg_nombre' => 'Ana Condori',
                'contacto_emerg_parentesco' => 'Madre',
                'contacto_emerg_telefono' => '74455668',
                'estado' => true,
            ]
        );

        User::updateOrCreate(
            ['username' => 'paciente1'],
            [
                'persona_id' => $pPac1->id,
                'rol_id' => $rolPac->id,
                'name' => 'Juan Mamani Condori',
                'email' => 'juan.paciente@gmail.com',
                'password' => Hash::make('paciente123'),
                'estado' => true,
            ]
        );

        // Paciente 2
        $pPac2 = Persona::updateOrCreate(
            ['ci' => '6000006'],
            [
                'nombres' => 'Lucía',
                'apellidos' => 'Fernández Ruiz',
                'telefono' => '75566778',
                'correo' => 'lucia.paciente@gmail.com',
                'estado' => true,
            ]
        );

        Paciente::updateOrCreate(
            ['persona_id' => $pPac2->id],
            [
                'codigo' => 'PAC-002',
                'fecha_nacimiento' => '1998-11-20',
                'sexo' => 'F',
                'direccion' => 'Barrio Hamacas C/5 #12, Santa Cruz',
                'tipo_sangre' => 'A+',
                'otras_alergias' => 'Alergia a la penicilina',
                'contacto_emerg_nombre' => 'Mario Fernández',
                'contacto_emerg_parentesco' => 'Padre',
                'contacto_emerg_telefono' => '75566779',
                'estado' => true,
            ]
        );

        User::updateOrCreate(
            ['username' => 'paciente2'],
            [
                'persona_id' => $pPac2->id,
                'rol_id' => $rolPac->id,
                'name' => 'Lucía Fernández Ruiz',
                'email' => 'lucia.paciente@gmail.com',
                'password' => Hash::make('paciente123'),
                'estado' => true,
            ]
        );
    }

    /**
     * Calcula y genera las fichas predeterminadas para un horario de atencion
     */
    private function generarFichasHorario(HorarioAtencion $horario): void
    {
        $inicio = Carbon::parse($horario->hora_inicio);
        $fin = Carbon::parse($horario->hora_fin);
        $duracion = $horario->duracion_cita_min ?? 30;
        $numero = 1;

        while ($inicio->copy()->addMinutes($duracion)->lte($fin)) {
            $slotFin = $inicio->copy()->addMinutes($duracion);

            Ficha::updateOrCreate(
                [
                    'horario_id' => $horario->id,
                    'numero' => $numero,
                ],
                [
                    'hora_inicio' => $inicio->format('H:i:s'),
                    'hora_fin' => $slotFin->format('H:i:s'),
                    'estado' => true,
                ]
            );

            $inicio = $slotFin;
            $numero++;
        }
    }
}
