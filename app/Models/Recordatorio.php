<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recordatorio extends Model
{
    protected $table = 'recordatorios';

    protected $fillable = [
        'cita_id',
        'tipo',
        'mensaje',
        'fecha_programada',
        'fecha_envio',
        'estado',
    ];

    protected $casts = [
        'fecha_programada' => 'datetime',
        'fecha_envio' => 'datetime',
    ];

    // Cita 1..1 -- 0..* Recordatorio
    public function cita()
    {
        return $this->belongsTo(Cita::class, 'cita_id');
    }
}
