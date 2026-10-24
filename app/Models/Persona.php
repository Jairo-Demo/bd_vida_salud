<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Persona extends Model
{
    protected $table = 'personas';

    protected $fillable = [
        'ci',
        'nombres',
        'apellidos',
        'telefono',
        'correo',
        'estado',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    public function paciente()
    {
        return $this->hasOne(Paciente::class, 'persona_id');
    }

    public function medico()
    {
        return $this->hasOne(Medico::class, 'persona_id');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'persona_id');
    }

    public function getNombreCompletoAttribute()
    {
        return "{$this->nombres} {$this->apellidos}";
    }
}
