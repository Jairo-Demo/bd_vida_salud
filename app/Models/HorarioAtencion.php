<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HorarioAtencion extends Model
{
    protected $table = 'horarios_atencion';

    protected $fillable = [
        'medico_id',
        'dia_semana',
        'hora_inicio',
        'hora_fin',
        'duracion_cita_min',
        'estado',
    ];

    protected $casts = [
        'duracion_cita_min' => 'integer',
        'estado' => 'boolean',
    ];

    public function medico()
    {
        return $this->belongsTo(Medico::class, 'medico_id');
    }

    public function fichas()
    {
        return $this->hasMany(Ficha::class, 'horario_id');
    }
}
