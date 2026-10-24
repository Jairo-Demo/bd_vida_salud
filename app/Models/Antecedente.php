<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Antecedente extends Model
{
    protected $table = 'antecedentes';

    protected $fillable = [
        'paciente_id',
        'consulta_id',
        'usuario_id',
        'tipo',
        'descripcion',
        'fecha_registro',
    ];

    protected $casts = [
        'fecha_registro' => 'date',
    ];

    // Paciente 1..1 -- 0..* Antecedente
    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    // Consulta 0..1 -- 0..* Antecedente
    public function consulta()
    {
        return $this->belongsTo(Consulta::class, 'consulta_id');
    }

    // Usuario 0..1 -- 0..* Antecedente (registra)
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
