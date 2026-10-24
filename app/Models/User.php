<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'persona_id',
        'rol_id',
        'name',
        'username',
        'email',
        'password',
        'estado',
        'ultimo_acceso',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'estado' => 'boolean',
            'ultimo_acceso' => 'datetime',
        ];
    }

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'persona_id');
    }

    public function rol()
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }

    public function bitacoras()
    {
        return $this->hasMany(Bitacora::class, 'usuario_id');
    }

    public function citasRegistradas()
    {
        return $this->hasMany(Cita::class, 'usuario_registro_id');
    }

    public function esAdmin(): bool
    {
        return $this->rol && strtolower($this->rol->nombre) === 'administrador';
    }

    public function esRecepcionista(): bool
    {
        return $this->rol && strtolower($this->rol->nombre) === 'recepcionista';
    }

    public function esMedico(): bool
    {
        return $this->rol && strtolower($this->rol->nombre) === 'médico';
    }

    public function esPaciente(): bool
    {
        return $this->rol && strtolower($this->rol->nombre) === 'paciente';
    }

    // Usuario 1..1 -- 0..* HistorialEstadoCita
    public function historialEstados()
    {
        return $this->hasMany(HistorialEstadoCita::class, 'usuario_id');
    }

    // Usuario 1..1 -- 0..* BloqueoAgenda
    public function bloqueosRegistrados()
    {
        return $this->hasMany(BloqueoAgenda::class, 'usuario_id');
    }

    // Usuario 0..1 -- 0..* Antecedente
    public function antecedentesRegistrados()
    {
        return $this->hasMany(Antecedente::class, 'usuario_id');
    }
}
