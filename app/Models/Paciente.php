<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Paciente extends Model
{
    protected $table = 'pacientes';

    protected $fillable = [
        'persona_id',
        'codigo',
        'fecha_nacimiento',
        'sexo',
        'direccion',
        'tipo_sangre',
        'otras_alergias',
        'contacto_emerg_nombre',
        'contacto_emerg_parentesco',
        'contacto_emerg_telefono',
        'estado',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'estado' => 'boolean',
    ];

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'persona_id');
    }

    public function citas()
    {
        return $this->hasMany(Cita::class, 'paciente_id');
    }

    public function getNombreCompletoAttribute()
    {
        return $this->persona ? $this->persona->nombre_completo : $this->codigo;
    }

    // Paciente 1..1 -- 0..1 Usuario (la cuenta se enlaza por la persona: users.persona_id)
    public function usuario()
    {
        return $this->hasOneThrough(User::class, Persona::class, 'id', 'persona_id', 'persona_id', 'id');
    }

    // Paciente 1..1 -- 0..* Antecedente
    public function antecedentes()
    {
        return $this->hasMany(Antecedente::class, 'paciente_id');
    }

    // Paciente 0..* -- 0..* Alergia (clase asociativa PacienteAlergia)
    public function alergias()
    {
        return $this->belongsToMany(Alergia::class, 'paciente_alergia', 'paciente_id', 'alergia_id')
            ->using(PacienteAlergia::class)
            ->withPivot('severidad', 'reaccion', 'fecha_registro')
            ->withTimestamps();
    }

    // Paciente 0..* -- 0..* Medicamento (clase asociativa PacienteMedicamento)
    public function medicamentos()
    {
        return $this->belongsToMany(Medicamento::class, 'paciente_medicamento', 'paciente_id', 'medicamento_id')
            ->using(PacienteMedicamento::class)
            ->withPivot('dosis', 'fecha_inicio')
            ->withTimestamps();
    }

    // Historia clinica: consultas del paciente recorriendo sus citas (RF19)
    public function consultas()
    {
        return $this->hasManyThrough(Consulta::class, Cita::class, 'paciente_id', 'cita_id');
    }
}
