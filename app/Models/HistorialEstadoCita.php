<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistorialEstadoCita extends Model
{
    protected $table = 'historial_estados_cita';

    protected $fillable = [
        'cita_id',
        'estado_anterior_id',
        'estado_nuevo_id',
        'usuario_id',
        'fecha_hora',
        'motivo',
    ];

    protected $casts = [
        'fecha_hora' => 'datetime',
    ];

    public function cita()
    {
        return $this->belongsTo(Cita::class, 'cita_id');
    }

    public function estadoAnterior()
    {
        return $this->belongsTo(EstadoCita::class, 'estado_anterior_id');
    }

    public function estadoNuevo()
    {
        return $this->belongsTo(EstadoCita::class, 'estado_nuevo_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
