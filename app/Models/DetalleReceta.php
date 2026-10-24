<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleReceta extends Model
{
    protected $table = 'detalle_recetas';

    protected $fillable = [
        'receta_id',
        'medicamento_id',
        'dosis',
        'frecuencia',
        'duracion_tratamiento',
        'cantidad',
        'indicaciones',
    ];

    protected $casts = [
        'cantidad' => 'integer',
    ];

    public function receta()
    {
        return $this->belongsTo(Receta::class, 'receta_id');
    }

    // Medicamento 1..1 -- 0..* DetalleReceta
    public function medicamento()
    {
        return $this->belongsTo(Medicamento::class, 'medicamento_id');
    }
}
