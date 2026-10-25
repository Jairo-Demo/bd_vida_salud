@extends('layouts.app')

@section('title', 'Fichas Calculadas - ' . $horario->dia_semana)

@push('styles')
<style>
    .schedule-header { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 24px 28px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
    .title { font-size: 20px; font-weight: 700; color: #0f172a; }
    .btn-back { background: #f1f5f9; color: #475569; padding: 8px 14px; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 600; }
    .fichas-section { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 24px 28px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
    .fichas-title { font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 4px; }
    .fichas-desc { font-size: 13px; color: #64748b; margin-bottom: 20px; }
    .fichas-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px; }
    .ficha-card { border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 16px; background: #f8fafc; display: flex; flex-direction: column; justify-content: space-between; transition: all 0.2s; }
    .ficha-card:hover { border-color: #0284c7; background: #ffffff; transform: translateY(-2px); box-shadow: 0 6px 16px rgba(2, 132, 199, 0.12); }
    .ficha-num { display: inline-block; background: linear-gradient(135deg, #0284c7 0%, #0d9488 100%); color: #ffffff; font-size: 11.5px; font-weight: 700; padding: 3px 8px; border-radius: 6px; margin-bottom: 8px; width: fit-content; }
    .ficha-time { font-size: 15px; font-weight: 700; color: #0f172a; margin-bottom: 4px; }
    .ficha-status { font-size: 11px; font-weight: 600; color: #15803d; }
</style>
@endpush

@section('content')

<div class="schedule-header">
    <div>
        <h1 class="title">Jornada: {{ $horario->dia_semana }} ({{ substr($horario->hora_inicio, 0, 5) }} - {{ substr($horario->hora_fin, 0, 5) }})</h1>
        <p style="font-size: 13px; color: #64748b; margin-top: 2px;">
            Médico: <strong>Dr(a). {{ $horario->medico->persona->nombre_completo ?? '—' }}</strong> ({{ $horario->medico->codigo }}) | 
            Duración: <strong>{{ $horario->duracion_cita_min }} min / turno</strong>
        </p>
    </div>
    <a href="{{ route('horarios.index', ['medico_id' => $horario->medico_id]) }}" class="btn-back">&larr; Volver a Horarios</a>
</div>

<div class="fichas-section">
    <h2 class="fichas-title">Fichas de Atención Predeterminadas (Clase Ficha — RF05 / CU13)</h2>
    <p class="fichas-desc">Bloques calculados a partir del horario semanal del médico. Estas fichas son consumidas al agendar citas.</p>

    <div class="fichas-grid">
        @forelse($horario->fichas as $ficha)
            <div class="ficha-card">
                <div>
                    <span class="ficha-num">Ficha #{{ $ficha->numero }}</span>
                    <div class="ficha-time">{{ substr($ficha->hora_inicio, 0, 5) }} &ndash; {{ substr($ficha->hora_fin, 0, 5) }}</div>
                </div>
                <div style="margin-top: 10px; display: flex; align-items: center; justify-content: space-between;">
                    <span class="ficha-status">● Habilitada</span>
                    <span style="font-size: 11px; color: #64748b;">{{ $horario->duracion_cita_min }} min</span>
                </div>
            </div>
        @empty
            <div style="grid-column: 1 / -1; text-align: center; padding: 24px; color: #64748b;">
                No se han generado fichas para este horario.
            </div>
        @endforelse
    </div>
</div>

@endsection
