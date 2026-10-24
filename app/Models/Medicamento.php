<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Medicamento extends Model
{
    protected $table = 'medicamentos';

    protected $fillable = [
        'nombre_comercial',
        'principio_activo',
        'presentacion',
        'estado',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // Medicamento 1..1 -- 0..* DetalleReceta
    public function detallesReceta()
    {
        return $this->hasMany(DetalleReceta::class, 'medicamento_id');
    }

    // Paciente 0..* -- 0..* Medicamento (clase asociativa PacienteMedicamento)
    public function pacientes()
    {
        return $this->belongsToMany(Paciente::class, 'paciente_medicamento', 'medicamento_id', 'paciente_id')
            ->using(PacienteMedicamento::class)
            ->withPivot('dosis', 'fecha_inicio')
            ->withTimestamps();
    }
}
