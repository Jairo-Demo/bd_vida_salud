<?php

namespace App\Console\Commands;

use App\Services\RecordatorioService;
use Illuminate\Console\Command;

/**
 * Tarea programada del CU31: envía por correo los recordatorios pendientes de las citas.
 * Se ejecuta cada hora con el Scheduler (routes/console.php) mediante el contenedor "scheduler".
 */
class EnviarRecordatorios extends Command
{
    protected $signature = 'recordatorios:enviar {--reintentar : Reintenta también los recordatorios fallidos}';

    protected $description = 'Envía los recordatorios de citas cuya fecha programada ya llegó (CU31)';

    public function handle(): int
    {
        $r = RecordatorioService::enviarPendientes((bool) $this->option('reintentar'));
        $this->info("Recordatorios enviados: {$r['enviados']} · fallidos: {$r['fallidos']} · omitidos: {$r['omitidos']}");
        return self::SUCCESS;
    }
}
