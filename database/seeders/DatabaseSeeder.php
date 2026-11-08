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
            PoblacionCompletaSeeder::class, // Puebla las 25 tablas con mínimo 10 registros cada una (Ciclo 2 completo)
        ]);
    }
}
