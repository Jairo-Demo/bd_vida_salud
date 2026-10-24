<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

// Clase asociativa entre Paciente y Alergia
class PacienteAlergia extends Pivot
{
    protected $table = 'paciente_alergia';

    public $incrementing = true;

    protected $fillable = [
        'paciente_id',
        'alergia_id',
        'severidad',
        'reaccion',
        'fecha_registro',
    ];

    protected $casts = [
        'fecha_registro' => 'date',
    ];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function alergia()
    {
        return $this->belongsTo(Alergia::class, 'alergia_id');
    }
}
