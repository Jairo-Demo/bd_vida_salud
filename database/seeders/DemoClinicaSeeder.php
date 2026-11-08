<?php

namespace Database\Seeders;

use App\Models\Alergia;
use App\Models\BloqueoAgenda;
use App\Models\Cita;
use App\Models\Consulta;
use App\Models\DetalleReceta;
use App\Models\Especialidad;
use App\Models\EstadoCita;
use App\Models\Ficha;
use App\Models\HistorialEstadoCita;
use App\Models\HorarioAtencion;
use App\Models\Medicamento;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\PacienteAlergia;
use App\Models\Receta;
use App\Models\Recordatorio;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder de Demostración Clínica (Ciclo 2):
 * Crea citas en todos los estados, historial, consultas atendidas, recetas y alergias
 * que coinciden exactamente con los 10 Casos de Prueba (CP01 a CP10) del documento Word.
 */
class DemoClinicaSeeder extends Seeder
{
    public function run(): void
    {
        $adminUser = User::where('username', 'admin')->first();
        if (!$adminUser) {
            return;
        }

        $paciente1 = Paciente::where('codigo', 'PAC-001')->first(); // Juan Mamani
        $paciente2 = Paciente::where('codigo', 'PAC-002')->first(); // Lucía Fernández
        $medico1 = Medico::where('codigo', 'MED-001')->first();     // Dr. Alejandro Roca
        $medico2 = Medico::where('codigo', 'MED-002')->first();     // Dra. Patricia Vargas

        if (!$paciente1 || !$paciente2 || !$medico1 || !$medico2) {
            return;
        }

        $espMedGeneral = Especialidad::where('nombre_normalizado', 'medicina_general')->first()
            ?? Especialidad::first();
        $espGineco = Especialidad::where('nombre_normalizado', 'ginecologia_y_obstetricia')->first()
            ?? $espMedGeneral;

        $estProg = EstadoCita::where('nombre', 'Programada')->first();
        $estConf = EstadoCita::where('nombre', 'Confirmada')->first();
        $estAten = EstadoCita::where('nombre', 'Atendida')->first();
        $estCanc = EstadoCita::where('nombre', 'Cancelada')->first();
        $estInas = EstadoCita::where('nombre', 'Inasistencia')->first();

        // -------------------------------------------------------------
        // 1. Alergia a la Penicilina de Lucía Fernández (CU26 / RN24)
        // -------------------------------------------------------------
        $alergiaPenicilina = Alergia::where('nombre', 'Penicilina')->first();
        if ($alergiaPenicilina) {
            PacienteAlergia::updateOrCreate(
                [
                    'paciente_id' => $paciente2->id,
                    'alergia_id' => $alergiaPenicilina->id,
                ],
                [
                    'severidad' => 'Grave',
                    'reaccion' => 'Shock anafiláctico / Urticaria severa generalizada',
                    'fecha_registro' => '2024-03-10',
                ]
            );
        }

        // Obtener fichas disponibles para generar citas
        $horarioM1 = HorarioAtencion::where('medico_id', $medico1->id)->first();
        $fichasM1 = $horarioM1 ? Ficha::where('horario_id', $horarioM1->id)->orderBy('numero')->get() : collect();

        $horarioM2 = HorarioAtencion::where('medico_id', $medico2->id)->first();
        $fichasM2 = $horarioM2 ? Ficha::where('horario_id', $horarioM2->id)->orderBy('numero')->get() : collect();

        if ($fichasM1->count() < 3 || $fichasM2->count() < 1) {
            return;
        }

        $hoy = Carbon::today();
        $ayer = Carbon::yesterday();
        $manana = Carbon::tomorrow();

        // -------------------------------------------------------------
        // 2. Cita CM-0001: Confirmada (Próxima / Futura)
        // -------------------------------------------------------------
        $ficha1 = $fichasM1[0];
        $cita1 = Cita::updateOrCreate(
            ['codigo' => 'CM-0001'],
            [
                'paciente_id' => $paciente1->id,
                'medico_id' => $medico1->id,
                'especialidad_id' => $espMedGeneral->id,
                'ficha_id' => $ficha1->id,
                'estado_id' => $estConf->id,
                'usuario_registro_id' => $adminUser->id,
                'fecha' => $manana->toDateString(),
                'hora_inicio' => $ficha1->hora_inicio,
                'hora_fin' => $ficha1->hora_fin,
                'motivo' => 'Control rutinario de presión arterial y chequeo general',
                'modalidad' => 'Presencial',
                'fecha_creacion' => now(),
            ]
        );

        HistorialEstadoCita::firstOrCreate(
            ['cita_id' => $cita1->id, 'estado_nuevo_id' => $estConf->id],
            [
                'estado_anterior_id' => $estProg->id,
                'usuario_id' => $adminUser->id,
                'fecha_hora' => now(),
                'motivo' => 'Asistencia confirmada por el paciente vía telefónica.',
            ]
        );

        // Recordatorio de cita por Email
        Recordatorio::updateOrCreate(
            ['cita_id' => $cita1->id, 'canal' => 'Email'],
            [
                'destinatario' => $paciente1->persona->correo,
                'mensaje' => "Estimado(a) {$paciente1->nombre_completo}, le recordamos su cita {$cita1->codigo} para mañana a las {$cita1->hora_inicio}.",
                'estado' => 'Enviado',
                'fecha_programada' => now()->subHours(2),
                'fecha_envio' => now()->subHour(),
            ]
        );

        // -------------------------------------------------------------
        // 3. Cita CM-0002: Cancelada (con motivo registrado - CU17)
        // -------------------------------------------------------------
        $ficha2 = $fichasM2[0];
        $cita2 = Cita::updateOrCreate(
            ['codigo' => 'CM-0002'],
            [
                'paciente_id' => $paciente2->id,
                'medico_id' => $medico2->id,
                'especialidad_id' => $espGineco->id,
                'ficha_id' => $ficha2->id,
                'estado_id' => $estCanc->id,
                'usuario_registro_id' => $adminUser->id,
                'fecha' => $ayer->toDateString(),
                'hora_inicio' => $ficha2->hora_inicio,
                'hora_fin' => $ficha2->hora_fin,
                'motivo' => 'Consulta ginecológica periódica',
                'modalidad' => 'Presencial',
                'motivo_cancelacion' => 'La paciente solicitó cancelación con 24h de anticipación por viaje de trabajo.',
                'fecha_creacion' => now()->subDays(3),
            ]
        );

        HistorialEstadoCita::firstOrCreate(
            ['cita_id' => $cita2->id, 'estado_nuevo_id' => $estCanc->id],
            [
                'estado_anterior_id' => $estProg->id,
                'usuario_id' => $adminUser->id,
                'fecha_hora' => now()->subDay(),
                'motivo' => 'Cancelación solicitada por el paciente: viaje de trabajo.',
            ]
        );

        // -------------------------------------------------------------
        // 4. Cita CM-0003: Inasistencia (No-Show - CU19)
        // -------------------------------------------------------------
        $ficha3 = $fichasM1[1];
        $cita3 = Cita::updateOrCreate(
            ['codigo' => 'CM-0003'],
            [
                'paciente_id' => $paciente2->id,
                'medico_id' => $medico1->id,
                'especialidad_id' => $espMedGeneral->id,
                'ficha_id' => $ficha3->id,
                'estado_id' => $estInas->id,
                'usuario_registro_id' => $adminUser->id,
                'fecha' => $ayer->toDateString(),
                'hora_inicio' => $ficha3->hora_inicio,
                'hora_fin' => $ficha3->hora_fin,
                'motivo' => 'Evaluación de malestar estomacal',
                'modalidad' => 'Presencial',
                'fecha_creacion' => now()->subDays(2),
            ]
        );

        HistorialEstadoCita::firstOrCreate(
            ['cita_id' => $cita3->id, 'estado_nuevo_id' => $estInas->id],
            [
                'estado_anterior_id' => $estConf->id,
                'usuario_id' => $adminUser->id,
                'fecha_hora' => now()->subDay(),
                'motivo' => 'El paciente no se presentó en su horario de turno asignado (No-Show).',
            ]
        );

        // -------------------------------------------------------------
        // 5. Cita CM-0004: Atendida con Consulta y Receta (CU23 / CU25 / CP09)
        // -------------------------------------------------------------
        $ficha4 = $fichasM1[2];
        $cita4 = Cita::updateOrCreate(
            ['codigo' => 'CM-0004'],
            [
                'paciente_id' => $paciente1->id,
                'medico_id' => $medico1->id,
                'especialidad_id' => $espMedGeneral->id,
                'ficha_id' => $ficha4->id,
                'estado_id' => $estAten->id,
                'usuario_registro_id' => $adminUser->id,
                'fecha' => $hoy->toDateString(),
                'hora_inicio' => $ficha4->hora_inicio,
                'hora_fin' => $ficha4->hora_fin,
                'motivo' => 'Dolor de cabeza agudo e insomnio',
                'modalidad' => 'Presencial',
                'fecha_creacion' => now()->subDays(1),
            ]
        );

        HistorialEstadoCita::firstOrCreate(
            ['cita_id' => $cita4->id, 'estado_nuevo_id' => $estAten->id],
            [
                'estado_anterior_id' => $estProg->id,
                'usuario_id' => $adminUser->id,
                'fecha_hora' => now(),
                'motivo' => 'Consulta completada satisfactoriamente por el facultativo.',
            ]
        );

        // Consulta médica
        $consulta = Consulta::updateOrCreate(
            ['cita_id' => $cita4->id],
            [
                'hora_inicio_real' => '09:00:00',
                'hora_fin_real' => '09:30:00',
                'sintomas' => 'Cefalea pulsátil frontal de 48 horas de evolución, fatiga visual y tensión muscular cervical.',
                'diagnostico' => 'Cefalea tensional leve a moderada por estrés laboral',
                'observaciones' => 'Paciente normotenso, hidratado, pupilas isocóricas fotorreactivas.',
                'peso' => 70.5,
                'presion_arterial' => '120/80',
                'temperatura' => 36.5,
                'indicaciones' => 'Reposo relativo, adecuada hidratación oral y pausas activas durante la jornada.',
            ]
        );

        // Receta médica
        $receta = Receta::updateOrCreate(
            ['consulta_id' => $consulta->id],
            [
                'codigo' => 'RX-0001',
                'fecha_emision' => $hoy->toDateString(),
                'observaciones' => 'Tomar con abundante agua después de los alimentos.',
            ]
        );

        $medParacetamol = Medicamento::where('principio_activo', 'Paracetamol')->first();
        if ($medParacetamol) {
            DetalleReceta::updateOrCreate(
                [
                    'receta_id' => $receta->id,
                    'medicamento_id' => $medParacetamol->id,
                ],
                [
                    'dosis' => '500 mg',
                    'frecuencia' => 'Cada 8 horas en caso de dolor',
                    'duracion_tratamiento' => '3 días',
                    'cantidad' => 10,
                    'indicaciones' => 'No superar 3 gramos al día.',
                ]
            );
        }

        // -------------------------------------------------------------
        // 6. Bloqueo de Agenda (CU30): Permiso de capacitación médica
        // -------------------------------------------------------------
        BloqueoAgenda::updateOrCreate(
            [
                'medico_id' => $medico2->id,
                'fecha_inicio' => $manana->copy()->addDays(5)->toDateString(),
            ],
            [
                'tipo' => 'Capacitación',
                'fecha_fin' => $manana->copy()->addDays(6)->toDateString(),
                'dia_completo' => true,
                'motivo' => 'Asistencia a Congreso Internacional de Ginecología y Salud Reproductiva.',
            ]
        );
    }
}
