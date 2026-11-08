<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// CU31 - Enviar recordatorio de cita: la tarea programada revisa los recordatorios cada hora
// (y reintenta los fallidos una vez al día). En Docker la ejecuta el contenedor "scheduler".
Schedule::command('recordatorios:enviar')->hourly()->withoutOverlapping();
Schedule::command('recordatorios:enviar --reintentar')->dailyAt('07:00')->withoutOverlapping();
