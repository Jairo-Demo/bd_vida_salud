<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolSeeder::class,
            PermisoSeeder::class,
            EstadoCitaSeeder::class,
            EspecialidadSeeder::class,
            DatosInicialesSeeder::class,
            CatalogoClinicoSeeder::class, // Ciclo 2: medicamentos y alergias (CU27)
            DemoClinicaSeeder::class,     // Ciclo 2: citas en todos los estados, consultas, recetas y alergias del Word
        ]);
    }
}
