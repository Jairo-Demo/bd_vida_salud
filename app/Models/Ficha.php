<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ficha extends Model
{
    protected $table = 'fichas';

    protected $fillable = [
        'horario_id',
        'numero',
        'hora_inicio',
        'hora_fin',
        'estado',
    ];

    protected $casts = [
        'numero' => 'integer',
        'estado' => 'boolean',
    ];

    public function horario()
    {
        return $this->belongsTo(HorarioAtencion::class, 'horario_id');
    }

    public function citas()
    {
        return $this->hasMany(Cita::class, 'ficha_id');
    }
}
