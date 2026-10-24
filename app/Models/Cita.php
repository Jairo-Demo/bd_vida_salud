<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cita extends Model
{
    protected $table = 'citas';

    protected $fillable = [
        'codigo',
        'paciente_id',
        'medico_id',
        'especialidad_id',
        'ficha_id',
        'estado_id',
        'usuario_registro_id',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'motivo',
        'modalidad',
        'motivo_cancelacion',
        'fecha_creacion',
    ];

    protected $casts = [
        'fecha' => 'date',
        'fecha_creacion' => 'datetime',
    ];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function medico()
    {
        return $this->belongsTo(Medico::class, 'medico_id');
    }

    public function especialidad()
    {
        return $this->belongsTo(Especialidad::class, 'especialidad_id');
    }

    public function ficha()
    {
        return $this->belongsTo(Ficha::class, 'ficha_id');
    }

    public function estado()
    {
        return $this->belongsTo(EstadoCita::class, 'estado_id');
    }

    public function usuarioRegistro()
    {
        return $this->belongsTo(User::class, 'usuario_registro_id');
    }

    public function historialEstados()
    {
        return $this->hasMany(HistorialEstadoCita::class, 'cita_id');
    }

    // Cita 1..1 -- 0..1 Consulta
    public function consulta()
    {
        return $this->hasOne(Consulta::class, 'cita_id');
    }

    // Cita 1..1 -- 0..* Recordatorio
    public function recordatorios()
    {
        return $this->hasMany(Recordatorio::class, 'cita_id');
    }
}
