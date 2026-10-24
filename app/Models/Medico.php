<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Medico extends Model
{
    protected $table = 'medicos';

    protected $fillable = [
        'persona_id',
        'codigo',
        'registro_profesional',
        'estado',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'persona_id');
    }

    public function especialidades()
    {
        return $this->belongsToMany(Especialidad::class, 'medico_especialidad', 'medico_id', 'especialidad_id')->withPivot('fecha_asignacion', 'estado')
            ->withTimestamps();
    }

    public function horarios()
    {
        return $this->hasMany(HorarioAtencion::class, 'medico_id');
    }

    public function citas()
    {
        return $this->hasMany(Cita::class, 'medico_id');
    }

    public function getNombreCompletoAttribute()
    {
        return $this->persona ? $this->persona->nombre_completo : $this->codigo;
    }

    // Medico 1..1 -- 0..1 Usuario (la cuenta se enlaza por la persona: users.persona_id)
    public function usuario()
    {
        return $this->hasOneThrough(User::class, Persona::class, 'id', 'persona_id', 'persona_id', 'id');
    }

    // Medico 0..1 -- 0..* BloqueoAgenda
    public function bloqueos()
    {
        return $this->hasMany(BloqueoAgenda::class, 'medico_id');
    }
}
