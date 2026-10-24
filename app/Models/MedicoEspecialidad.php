<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Clase asociativa Medico 0..* -- 1..* Especialidad
class MedicoEspecialidad extends Model
{
    protected $table = 'medico_especialidad';

    protected $fillable = [
        'medico_id',
        'especialidad_id',
        'fecha_asignacion',
        'estado',
    ];

    protected $casts = [
        'fecha_asignacion' => 'date',
        'estado' => 'boolean',
    ];

    public function medico()
    {
        return $this->belongsTo(Medico::class, 'medico_id');
    }

    public function especialidad()
    {
        return $this->belongsTo(Especialidad::class, 'especialidad_id');
    }
}
