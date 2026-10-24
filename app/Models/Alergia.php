<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alergia extends Model
{
    protected $table = 'alergias';

    protected $fillable = [
        'nombre',
        'estado',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // Paciente 0..* -- 0..* Alergia (clase asociativa PacienteAlergia)
    public function pacientes()
    {
        return $this->belongsToMany(Paciente::class, 'paciente_alergia', 'alergia_id', 'paciente_id')
            ->using(PacienteAlergia::class)
            ->withPivot('severidad', 'reaccion', 'fecha_registro')
            ->withTimestamps();
    }
}
