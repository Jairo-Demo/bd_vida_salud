<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

// Clase asociativa entre Paciente y Medicamento
class PacienteMedicamento extends Pivot
{
    protected $table = 'paciente_medicamento';

    public $incrementing = true;

    protected $fillable = [
        'paciente_id',
        'medicamento_id',
        'dosis',
        'fecha_inicio',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
    ];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function medicamento()
    {
        return $this->belongsTo(Medicamento::class, 'medicamento_id');
    }
}
