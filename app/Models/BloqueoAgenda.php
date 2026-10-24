<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BloqueoAgenda extends Model
{
    protected $table = 'bloqueos_agenda';

    protected $fillable = [
        'medico_id',
        'usuario_id',
        'fecha_inicio',
        'fecha_fin',
        'hora_inicio',
        'hora_fin',
        'tipo',
        'motivo',
        'estado',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'estado' => 'boolean',
    ];

    // Medico 0..1 -- 0..* BloqueoAgenda
    public function medico()
    {
        return $this->belongsTo(Medico::class, 'medico_id');
    }

    // Usuario 1..1 -- 0..* BloqueoAgenda (registra)
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
