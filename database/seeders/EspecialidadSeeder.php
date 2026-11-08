<?php

namespace Database\Seeders;

use App\Models\Especialidad;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EspecialidadSeeder extends Seeder
{
    public function run(): void
    {
        $especialidades = [
            ['nombre' => 'Medicina General', 'nombre_normalizado' => 'medicina_general', 'descripcion' => 'Atención médica primaria y triaje clínico.', 'estado' => true],
            ['nombre' => 'Pediatría', 'nombre_normalizado' => 'pediatria', 'descripcion' => 'Atención pediátrica y control de desarrollo infantil.', 'estado' => true],
            ['nombre' => 'Ginecología y Obstetricia', 'nombre_normalizado' => 'ginecologia_y_obstetricia', 'descripcion' => 'Salud femenina y control prenatal.', 'estado' => true],
            ['nombre' => 'Odontología', 'nombre_normalizado' => 'odontologia', 'descripcion' => 'Salud bucodental y profilaxis.', 'estado' => true],
            ['nombre' => 'Cardiología', 'nombre_normalizado' => 'cardiologia', 'descripcion' => 'Enfermedades cardiovasculares e hipertensión.', 'estado' => true],
            ['nombre' => 'Traumatología', 'nombre_normalizado' => 'traumatologia', 'descripcion' => 'Lesiones articulares y traumatismos.', 'estado' => true],
            ['nombre' => 'Dermatología', 'nombre_normalizado' => 'dermatologia', 'descripcion' => 'Tratamiento de afecciones de la piel y mucosas.', 'estado' => true],
            ['nombre' => 'Neurología', 'nombre_normalizado' => 'neurologia', 'descripcion' => 'Diagnóstico del sistema nervioso central y periférico.', 'estado' => true],
            ['nombre' => 'Oftalmología', 'nombre_normalizado' => 'oftalmologia', 'descripcion' => 'Salud visual y patologías oculares.', 'estado' => true],
            ['nombre' => 'Endocrinología', 'nombre_normalizado' => 'endocrinologia', 'descripcion' => 'Control de diabetes, tiroides y trastornos metabólicos.', 'estado' => true],
        ];

        foreach ($especialidades as $esp) {
            Especialidad::updateOrCreate(['nombre_normalizado' => $esp['nombre_normalizado']], $esp);
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("SELECT setval(pg_get_serial_sequence('especialidades', 'id'), COALESCE((SELECT MAX(id) FROM especialidades), 1), true);");
        }
    }
}
