<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstadoCita extends Model
{
    protected $table = 'estados_cita';

    protected $fillable = [
        'nombre',
        'descripcion',
    ];

    public function citas()
    {
        return $this->hasMany(Cita::class, 'estado_id');
    }

    // EstadoCita 1..1 (nuevo) -- 0..* HistorialEstadoCita
    public function historialComoNuevo()
    {
        return $this->hasMany(HistorialEstadoCita::class, 'estado_nuevo_id');
    }

    // EstadoCita 0..1 (anterior) -- 0..* HistorialEstadoCita
    public function historialComoAnterior()
    {
        return $this->hasMany(HistorialEstadoCita::class, 'estado_anterior_id');
    }
}
