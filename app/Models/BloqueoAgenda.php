<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BloqueoAgenda extends Model
{
    protected $table = 'bloqueos_agenda';

    protected $fillable = [
        'medico_id',
        'usuario_id',
        'fecha_inicio',
        'fecha_fin',
        'hora_inicio',
        'hora_fin',
        'tipo',
        'motivo',
        'estado',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'estado' => 'boolean',
    ];

    // Medico 0..1 -- 0..* BloqueoAgenda
    public function medico()
    {
        return $this->belongsTo(Medico::class, 'medico_id');
    }

    // Usuario 1..1 -- 0..* BloqueoAgenda (registra)
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /** Bloqueos activos que cubren la fecha para el médico (incluye los generales sin médico). CU30 */
    public function scopeVigentesPara($query, $medicoId, string $fecha)
    {
        return $query->where('estado', true)
            ->whereDate('fecha_inicio', '<=', $fecha)
            ->whereDate('fecha_fin', '>=', $fecha)
            ->where(fn ($q) => $q->whereNull('medico_id')->orWhere('medico_id', $medicoId));
    }

    /** ¿Bloquea el día completo? (sin rango de horas) */
    public function esDiaCompleto(): bool
    {
        return !$this->hora_inicio || !$this->hora_fin;
    }

    /** ¿Se cruza con un rango horario (por ejemplo, una ficha)? */
    public function afectaHorario(string $inicio, string $fin): bool
    {
        if ($this->esDiaCompleto()) {
            return true;
        }
        return substr($inicio, 0, 5) < substr($this->hora_fin, 0, 5) && substr($fin, 0, 5) > substr($this->hora_inicio, 0, 5);
    }

    public function getDescripcionPeriodoAttribute(): string
    {
        $txt = $this->fecha_inicio->format('d/m/Y');
        if (!$this->fecha_fin->isSameDay($this->fecha_inicio)) {
            $txt .= ' al ' . $this->fecha_fin->format('d/m/Y');
        }
        if (!$this->esDiaCompleto()) {
            $txt .= ' (' . substr($this->hora_inicio, 0, 5) . ' - ' . substr($this->hora_fin, 0, 5) . ')';
        }
        return $txt;
    }
}
