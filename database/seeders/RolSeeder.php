<?php

namespace Database\Seeders;

use App\Models\Rol;
use Illuminate\Database\Seeder;

class RolSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'id' => 1,
                'nombre' => 'Administrador',
                'descripcion' => 'Control total del sistema, gestion de usuarios, roles, configuraciones y auditoria.',
                'estado' => true,
            ],
            [
                'id' => 2,
                'nombre' => 'Recepcionista',
                'descripcion' => 'Registro de pacientes, programacion de citas presenciales, gestion de turnos y agenda.',
                'estado' => true,
            ],
            [
                'id' => 3,
                'nombre' => 'Medico',
                'descripcion' => 'Visualizacion de agenda propia, atencion de consultas medicas y gestion de fichas.',
                'estado' => true,
            ],
            [
                'id' => 4,
                'nombre' => 'Paciente',
                'descripcion' => 'Portal de autoservicio: reserva, consulta y cancelacion de citas propias.',
                'estado' => true,
            ],
        ];

        foreach ($roles as $rol) {
            Rol::updateOrCreate(['id' => $rol['id']], $rol);
        }
    }
}
