<?php

namespace Database\Seeders;

use App\Models\Especialidad;
use Illuminate\Database\Seeder;

class EspecialidadSeeder extends Seeder
{
    public function run(): void
    {
        $especialidades = [
            [
                'id' => 1,
                'nombre' => 'Medicina General',
                'nombre_normalizado' => 'medicina_general',
                'descripcion' => 'Atencion medica primaria, evaluacion general de salud y triaje clinico.',
                'estado' => true,
            ],
            [
                'id' => 2,
                'nombre' => 'Pediatría',
                'nombre_normalizado' => 'pediatria',
                'descripcion' => 'Cuidado integral de la salud, crecimiento y enfermedades de ninos y adolescentes.',
                'estado' => true,
            ],
            [
                'id' => 3,
                'nombre' => 'Ginecología y Obstetricia',
                'nombre_normalizado' => 'ginecologia_y_obstetricia',
                'descripcion' => 'Salud reproductiva femenina, control prenatal y patologias ginecologicas.',
                'estado' => true,
            ],
            [
                'id' => 4,
                'nombre' => 'Odontología',
                'nombre_normalizado' => 'odontologia',
                'descripcion' => 'Salud bucodental, profilaxis, tratamientos restaurativos y prevencion.',
                'estado' => true,
            ],
            [
                'id' => 5,
                'nombre' => 'Cardiología',
                'nombre_normalizado' => 'cardiologia',
                'descripcion' => 'Diagnostico y tratamiento de enfermedades cardiovasculares e hipertension.',
                'estado' => true,
            ],
            [
                'id' => 6,
                'nombre' => 'Traumatología',
                'nombre_normalizado' => 'traumatologia',
                'descripcion' => 'Tratamiento de lesiones musculoesqueleticas, fracturas y articulaciones.',
                'estado' => true,
            ],
        ];

        foreach ($especialidades as $esp) {
            Especialidad::updateOrCreate(['id' => $esp['id']], $esp);
        }
    }
}
