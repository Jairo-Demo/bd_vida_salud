<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Consulta extends Model
{
    protected $table = 'consultas';

    protected $fillable = [
        'cita_id',
        'hora_inicio_real',
        'hora_fin_real',
        'sintomas',
        'diagnostico',
        'observaciones',
        'peso',
        'presion_arterial',
        'temperatura',
        'indicaciones',
    ];

    protected $casts = [
        'peso' => 'decimal:2',
        'temperatura' => 'decimal:1',
    ];

    // Cita 1..1 -- 0..1 Consulta
    public function cita()
    {
        return $this->belongsTo(Cita::class, 'cita_id');
    }

    // Consulta 1..1 -- 0..1 Receta
    public function receta()
    {
        return $this->hasOne(Receta::class, 'consulta_id');
    }

    // Consulta 0..1 -- 0..* Antecedente
    public function antecedentes()
    {
        return $this->hasMany(Antecedente::class, 'consulta_id');
    }
}
