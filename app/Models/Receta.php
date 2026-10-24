<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Receta extends Model
{
    protected $table = 'recetas';

    protected $fillable = [
        'consulta_id',
        'codigo',
        'fecha_emision',
        'observaciones',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
    ];

    // Consulta 1..1 -- 0..1 Receta
    public function consulta()
    {
        return $this->belongsTo(Consulta::class, 'consulta_id');
    }

    // Receta 1..1 <>-- 1..* DetalleReceta (composicion)
    public function detalles()
    {
        return $this->hasMany(DetalleReceta::class, 'receta_id');
    }

    // Medicamentos de la receta, obtenidos a traves de DetalleReceta (no hay relacion directa)
    public function medicamentos()
    {
        return $this->belongsToMany(Medicamento::class, 'detalle_recetas', 'receta_id', 'medicamento_id')
            ->withPivot('dosis', 'frecuencia', 'duracion_tratamiento', 'cantidad', 'indicaciones')
            ->withTimestamps();
    }
}
