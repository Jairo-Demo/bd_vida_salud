<?php

namespace Database\Seeders;

use App\Models\Alergia;
use App\Models\Medicamento;
use Illuminate\Database\Seeder;

/**
 * Ciclo 2 - Catalogos clinicos iniciales (CU27): medicamentos y alergias.
 * Se usan al emitir recetas (CU25) y al verificar alergias (CU26 / fn_paciente_alergico).
 */
class CatalogoClinicoSeeder extends Seeder
{
    public function run(): void
    {
        $medicamentos = [
            ['nombre_comercial' => 'Paracetamol', 'principio_activo' => 'Paracetamol', 'presentacion' => 'Comprimido 500 mg'],
            ['nombre_comercial' => 'Ibuprofeno', 'principio_activo' => 'Ibuprofeno', 'presentacion' => 'Comprimido 400 mg'],
            ['nombre_comercial' => 'Amoxil', 'principio_activo' => 'Amoxicilina', 'presentacion' => 'Capsula 500 mg'],
            ['nombre_comercial' => 'Penicilina G Benzatinica', 'principio_activo' => 'Penicilina', 'presentacion' => 'Inyectable 1.200.000 UI'],
            ['nombre_comercial' => 'Aspirina', 'principio_activo' => 'Acido acetilsalicilico', 'presentacion' => 'Comprimido 100 mg'],
            ['nombre_comercial' => 'Diclofenaco', 'principio_activo' => 'Diclofenaco', 'presentacion' => 'Comprimido 50 mg'],
            ['nombre_comercial' => 'Loratadina', 'principio_activo' => 'Loratadina', 'presentacion' => 'Comprimido 10 mg'],
            ['nombre_comercial' => 'Omeprazol', 'principio_activo' => 'Omeprazol', 'presentacion' => 'Capsula 20 mg'],
            ['nombre_comercial' => 'Metformina', 'principio_activo' => 'Metformina', 'presentacion' => 'Comprimido 850 mg'],
            ['nombre_comercial' => 'Losartan', 'principio_activo' => 'Losartan', 'presentacion' => 'Comprimido 50 mg'],
            ['nombre_comercial' => 'Salbutamol', 'principio_activo' => 'Salbutamol', 'presentacion' => 'Inhalador 100 mcg'],
            ['nombre_comercial' => 'Bactrim', 'principio_activo' => 'Sulfametoxazol + Trimetoprima', 'presentacion' => 'Comprimido 800/160 mg'],
        ];

        foreach ($medicamentos as $m) {
            Medicamento::updateOrCreate(
                ['nombre_comercial' => $m['nombre_comercial'], 'presentacion' => $m['presentacion']],
                $m + ['estado' => true]
            );
        }

        $alergias = [
            'Penicilina', 'Amoxicilina', 'Ibuprofeno', 'Acido acetilsalicilico', 'Diclofenaco',
            'Sulfametoxazol', 'Latex', 'Mariscos', 'Polen', 'Mani',
        ];

        foreach ($alergias as $nombre) {
            Alergia::updateOrCreate(['nombre' => $nombre], ['estado' => true]);
        }
    }
}
