<?php

namespace Database\Seeders;

use App\Models\EstadoCita;
use Illuminate\Database\Seeder;

class EstadoCitaSeeder extends Seeder
{
    public function run(): void
    {
        $estados = [
            [
                'id' => 1,
                'nombre' => 'Programada',
                'descripcion' => 'Cita agendada exitosamente, pendiente de confirmacion o atencion.',
            ],
            [
                'id' => 2,
                'nombre' => 'Confirmada',
                'descripcion' => 'Cita reconfirmada por el paciente o el personal de recepcion.',
            ],
            [
                'id' => 3,
                'nombre' => 'Atendida',
                'descripcion' => 'Consulta medica completada satisfactoriamente por el facultativo.',
            ],
            [
                'id' => 4,
                'nombre' => 'Cancelada',
                'descripcion' => 'Cita anulada formalmente con registro de motivo de cancelacion.',
            ],
            [
                'id' => 5,
                'nombre' => 'Inasistencia',
                'descripcion' => 'El paciente no se presento en la fecha y horario pactados.',
            ],
        ];

        foreach ($estados as $est) {
            EstadoCita::updateOrCreate(['id' => $est['id']], $est);
        }
    }
}
