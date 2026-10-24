<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Especialidad extends Model
{
    protected $table = 'especialidades';

    protected $fillable = [
        'nombre',
        'nombre_normalizado',
        'descripcion',
        'estado',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    public function medicos()
    {
        return $this->belongsToMany(Medico::class, 'medico_especialidad', 'especialidad_id', 'medico_id')
            ->withPivot('fecha_asignacion', 'estado')
            ->withTimestamps();
    }

    public function citas()
    {
        return $this->hasMany(Cita::class, 'especialidad_id');
    }
}
