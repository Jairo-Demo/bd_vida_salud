@extends('layouts.app')

@section('title', 'Cancelar Cita ' . $cita->codigo)

@push('styles')
<style>
    .cancel-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 28px 32px; max-width: 760px; margin: 0 auto; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
    .cancel-title { font-size: 20px; font-weight: 700; color: #0f172a; margin-bottom: 4px; }
    .cancel-desc { font-size: 13px; color: #64748b; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; }
    .row-detail { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f8fafc; font-size: 13.5px; }
    .lbl { color: #64748b; font-weight: 500; }
    .val { color: #0f172a; font-weight: 600; text-align: right; }
    .warn { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; border-radius: 10px; padding: 12px 16px; font-size: 13px; margin: 18px 0; }
    .form-label { font-size: 12.5px; font-weight: 600; color: #334155; display: block; margin-bottom: 6px; }
    .form-control { width: 100%; padding: 9px 13px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; outline: none; }
    .form-control:focus { border-color: #dc2626; }
    .invalid-feedback { font-size: 11.5px; color: #dc2626; margin-top: 4px; display: block; }
    .form-actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; }
    .btn-danger { background: #dc2626; color: #fff; padding: 10px 22px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 14px; }
    .btn-back { background: #f1f5f9; color: #475569; padding: 10px 18px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 13.5px; }
</style>
@endpush

@section('content')
<div class="cancel-card">
    <h1 class="cancel-title">✕ Cancelar Cita {{ $cita->codigo }}</h1>
    <p class="cancel-desc">La cita no se elimina: cambia a estado Cancelada, se guarda el motivo y queda en el historial (CU17 / RN21).</p>

    <div class="row-detail"><span class="lbl">Paciente</span><span class="val">{{ $cita->paciente->nombre_completo }}</span></div>
    <div class="row-detail"><span class="lbl">Médico</span><span class="val">Dr(a). {{ $cita->medico->persona->nombre_completo ?? '—' }} · {{ $cita->especialidad->nombre }}</span></div>
    <div class="row-detail"><span class="lbl">Fecha y ficha</span><span class="val">{{ $cita->fecha->format('d/m/Y') }} · {{ substr($cita->hora_inicio, 0, 5) }} - {{ substr($cita->hora_fin, 0, 5) }}</span></div>
    <div class="row-detail"><span class="lbl">Estado actual</span><span class="val">{{ $cita->estadoCita->nombre }}</span></div>

    <div class="warn">⚠ Al confirmar, la ficha de las {{ substr($cita->hora_inicio, 0, 5) }} del {{ $cita->fecha->format('d/m/Y') }} quedará libre para nuevas reservas.</div>

    <form action="{{ route('citas.cancelar.update', $cita) }}" method="POST">
        @csrf
        @method('PATCH')
        <label class="form-label" for="motivo_cancelacion">Motivo de la cancelación <span style="color:#ef4444;">*</span></label>
        <textarea name="motivo_cancelacion" id="motivo_cancelacion" rows="3" class="form-control" required minlength="5" maxlength="500"
                  placeholder="Ej. Viaje del paciente, el paciente se recuperó...">{{ old('motivo_cancelacion') }}</textarea>
        @error('motivo_cancelacion') <span class="invalid-feedback">{{ $message }}</span> @enderror

        <div class="form-actions">
            <a href="{{ route('citas.show', $cita) }}" class="btn-back">Volver</a>
            <button type="submit" class="btn-danger">Confirmar cancelación</button>
        </div>
    </form>
</div>
@endsection
