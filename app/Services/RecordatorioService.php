<?php

namespace App\Services;

use App\Mail\RecordatorioCitaMail;
use App\Models\Cita;
use App\Models\Recordatorio;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Recordatorios de citas (Paquete P8 / CU31 Enviar recordatorio de cita).
 */
class RecordatorioService
{
    /** CU31 - Alterno A: al programar o reprogramar se agenda el recordatorio 24 horas antes de la cita. */
    public static function programarProximaCita(Cita $cita): void
    {
        $inicio = Carbon::parse($cita->fecha->toDateString() . ' ' . $cita->hora_inicio);
        $envio = $inicio->copy()->subDay();
        if ($envio->isPast()) {
            $envio = now();
        }
        $mensaje = "Le recordamos su cita {$cita->codigo} el " . $inicio->format('d/m/Y') . ' a las ' . $inicio->format('H:i') . '.';

        $pendiente = Recordatorio::where('cita_id', $cita->id)->where('tipo', 'Proxima cita')->where('estado', 'Pendiente')->first();
        if ($pendiente) {
            $pendiente->update(['fecha_programada' => $envio, 'mensaje' => $mensaje]);
        } else {
            Recordatorio::create(['cita_id' => $cita->id, 'tipo' => 'Proxima cita', 'mensaje' => $mensaje, 'fecha_programada' => $envio, 'estado' => 'Pendiente']);
        }
    }

    /** Al cancelar la cita, el recordatorio de próxima cita pendiente ya no se envía. */
    public static function anularProximaCita(Cita $cita): void
    {
        Recordatorio::where('cita_id', $cita->id)->where('tipo', 'Proxima cita')->where('estado', 'Pendiente')
            ->update(['estado' => 'Fallido', 'mensaje' => 'Omitido: la cita fue cancelada.', 'updated_at' => now()]);
    }

    /**
     * Envía los recordatorios cuya fecha programada ya llegó.
     * @return array{enviados:int, fallidos:int, omitidos:int}
     */
    public static function enviarPendientes(bool $reintentarFallidos = false, ?int $soloId = null): array
    {
        $res = ['enviados' => 0, 'fallidos' => 0, 'omitidos' => 0];

        $recordatorios = Recordatorio::with(['cita.paciente.persona', 'cita.medico.persona', 'cita.especialidad', 'cita.estadoCita', 'cita.ficha'])
            ->when($soloId, fn ($q) => $q->whereKey($soloId))
            ->when(!$soloId, fn ($q) => $q->whereIn('estado', $reintentarFallidos ? ['Pendiente', 'Fallido'] : ['Pendiente'])
                ->where('fecha_programada', '<=', now()))
            ->when(!$soloId && $reintentarFallidos, fn ($q) => $q->where(fn ($w) => $w->whereNull('mensaje')->orWhere('mensaje', 'not like', 'Omitido:%')))
            ->orderBy('fecha_programada')
            ->limit(200)
            ->get();

        foreach ($recordatorios as $r) {
            $cita = $r->cita;

            // Un recordatorio de próxima cita no se envía si la cita ya no está activa o ya pasó
            if ($r->tipo === 'Proxima cita' && (!in_array($cita->estadoCita->nombre, ['Programada', 'Confirmada'], true)
                    || Carbon::parse($cita->fecha->toDateString() . ' ' . $cita->hora_inicio)->isPast())) {
                $r->update(['estado' => 'Fallido', 'mensaje' => "Omitido: la cita está {$cita->estadoCita->nombre} o ya pasó."]);
                $res['omitidos']++;
                continue;
            }

            $correo = $cita->paciente->persona->correo ?? null;
            if (!$correo || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                $r->update(['estado' => 'Fallido']);
                BitacoraService::registrarSistema('RECORDATORIO_FALLIDO', 'recordatorios', $r->id, "Cita {$cita->codigo}: el paciente no tiene un correo válido.");
                $res['fallidos']++;
                continue;
            }

            try {
                Mail::to($correo)->send(new RecordatorioCitaMail($r));
                $r->update(['estado' => 'Enviado', 'fecha_envio' => now()]);
                BitacoraService::registrarSistema('ENVIAR_RECORDATORIO', 'recordatorios', $r->id, "Recordatorio '{$r->tipo}' de la cita {$cita->codigo} enviado a {$correo}.");
                $res['enviados']++;
            } catch (\Throwable $e) {
                Log::error("Recordatorio {$r->id}: " . $e->getMessage());
                $r->update(['estado' => 'Fallido']);
                BitacoraService::registrarSistema('RECORDATORIO_FALLIDO', 'recordatorios', $r->id, "Cita {$cita->codigo}: falló el envío del correo (se reintentará).");
                $res['fallidos']++;
            }
        }
        return $res;
    }
}
