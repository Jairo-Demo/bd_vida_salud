@extends('layouts.app')

@section('title', 'Modificar Horario')

@push('styles')
<style>
    .form-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 28px 32px; max-width: 680px; margin: 0 auto; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
    .form-title { font-size: 20px; font-weight: 700; color: #0f172a; margin-bottom: 4px; }
    .form-desc { font-size: 13px; color: #64748b; margin-bottom: 22px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; }
    .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px 20px; }
    .form-group { display: flex; flex-direction: column; gap: 6px; }
    .form-label { font-size: 12.5px; font-weight: 600; color: #334155; }
    .form-control { padding: 9px 13px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; outline: none; background: #fff; }
    .form-control:focus { border-color: #0284c7; }
    .invalid-feedback { font-size: 11.5px; color: #dc2626; margin-top: 2px; }
    .form-actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 28px; padding-top: 18px; border-top: 1px solid #f1f5f9; }
    .btn-save { background: linear-gradient(135deg, #0284c7 0%, #0d9488 100%); color: #fff; padding: 10px 22px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; }
    .btn-cancel { background: #f1f5f9; color: #475569; padding: 10px 18px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 13.5px; }
</style>
@endpush

@section('content')

<div class="form-card">
    <h1 class="form-title">Modificar Jornada de Atención</h1>
    <p class="form-desc">Médico: <strong>Dr(a). {{ $horario->medico->persona->nombre_completo ?? '—' }}</strong></p>

    <form action="{{ route('horarios.update', $horario) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="form-grid">
            <div class="form-group" style="grid-column: 1 / -1;">
                <label class="form-label" for="dia_semana">Día de la Semana <span style="color: #ef4444;">*</span></label>
                <select name="dia_semana" id="dia_semana" class="form-control" required>
                    @foreach($diasSemana as $dia)
                        <option value="{{ $dia }}" {{ old('dia_semana', $horario->dia_semana) === $dia ? 'selected' : '' }}>{{ $dia }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="hora_inicio">Hora Inicial <span style="color: #ef4444;">*</span></label>
                <input type="time" name="hora_inicio" id="hora_inicio" class="form-control @error('hora_inicio') is-invalid @enderror" value="{{ old('hora_inicio', substr($horario->hora_inicio, 0, 5)) }}" required>
                @error('hora_inicio') <span class="invalid-feedback">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="hora_fin">Hora Final <span style="color: #ef4444;">*</span></label>
                <input type="time" name="hora_fin" id="hora_fin" class="form-control @error('hora_fin') is-invalid @enderror" value="{{ old('hora_fin', substr($horario->hora_fin, 0, 5)) }}" required>
                @error('hora_fin') <span class="invalid-feedback">{{ $message }}</span> @enderror
            </div>

            <div class="form-group" style="grid-column: 1 / -1;">
                <label class="form-label" for="duracion_cita_min">Duración por Consulta (Minutos) <span style="color: #ef4444;">*</span></label>
                <input type="number" name="duracion_cita_min" id="duracion_cita_min" class="form-control" value="{{ old('duracion_cita_min', $horario->duracion_cita_min) }}" min="10" max="120" step="5" required>
            </div>
        </div>

        <div class="form-actions">
            <a href="{{ route('horarios.show', $horario) }}" class="btn-cancel">Cancelar</a>
            <button type="submit" class="btn-save">Recalcular y Actualizar</button>
        </div>
    </form>
</div>

@endsection
