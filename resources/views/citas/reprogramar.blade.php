@extends('layouts.app')

@section('title', 'Reprogramar Cita ' . $cita->codigo)

@push('styles')
<style>
    .booking-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 28px 32px; max-width: 900px; margin: 0 auto; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
    .booking-title { font-size: 20px; font-weight: 700; color: #0f172a; margin-bottom: 4px; }
    .booking-desc { font-size: 13px; color: #64748b; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; }
    .form-section-title { font-size: 13.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #0284c7; margin: 20px 0 12px; }
    .current-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; }
    .cur-lbl { font-size: 11.5px; color: #64748b; font-weight: 600; text-transform: uppercase; }
    .cur-val { font-size: 14px; color: #0f172a; font-weight: 700; margin-top: 2px; }
    .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px 20px; }
    .form-group { display: flex; flex-direction: column; gap: 6px; }
    .form-group.full { grid-column: 1 / -1; }
    .form-label { font-size: 12.5px; font-weight: 600; color: #334155; }
    .form-control { padding: 9px 13px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; outline: none; background: #fff; }
    .form-control:focus { border-color: #0284c7; }
    .invalid-feedback { font-size: 11.5px; color: #dc2626; margin-top: 2px; }
    .fichas-container { background: #f8fafc; border: 1.5px dashed #cbd5e1; border-radius: 12px; padding: 18px; margin-top: 6px; }
    .fichas-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 10px; margin-top: 10px; }
    .ficha-pill { border: 1.5px solid #cbd5e1; background: #fff; border-radius: 8px; padding: 10px; text-align: center; cursor: pointer; transition: all 0.15s; }
    .ficha-pill:hover:not(.disabled) { border-color: #0284c7; background: #f0fdf4; }
    .ficha-pill input { display: none; }
    .ficha-pill.selected { border-color: #0284c7; background: #e0f2fe; box-shadow: 0 0 0 2px #0284c7; }
    .ficha-pill.disabled { opacity: 0.5; background: #e2e8f0; cursor: not-allowed; }
    .ficha-pill.actual { border-style: dashed; }
    .ficha-time { font-size: 13px; font-weight: 700; color: #0f172a; }
    .ficha-tag { font-size: 10.5px; font-weight: 600; margin-top: 2px; }
    .form-actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 28px; padding-top: 18px; border-top: 1px solid #f1f5f9; }
    .btn-save { background: linear-gradient(135deg, #0284c7 0%, #0d9488 100%); color: #fff; padding: 10px 24px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 14px; }
    .btn-cancel { background: #f1f5f9; color: #475569; padding: 10px 18px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 13.5px; }
    .alert-box { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; border-radius: 10px; padding: 12px 16px; font-size: 13px; margin-bottom: 16px; }
</style>
@endpush

@section('content')
<div class="booking-card">
    <h1 class="booking-title">🔁 Reprogramar Cita {{ $cita->codigo }}</h1>
    <p class="booking-desc">Seleccione la nueva fecha y una ficha libre del mismo médico. La ficha actual quedará libre para otros pacientes (CU16).</p>

    @if($errors->any())
        <div class="alert-box">
            @foreach($errors->all() as $e) <div>• {{ $e }}</div> @endforeach
        </div>
    @endif

    <!-- Datos actuales de la cita -->
    <div class="form-section-title">1. Cita actual</div>
    <div class="current-grid">
        <div><div class="cur-lbl">Paciente</div><div class="cur-val">{{ $cita->paciente->nombre_completo }}</div></div>
        <div><div class="cur-lbl">Médico</div><div class="cur-val">Dr(a). {{ $cita->medico->persona->nombre_completo ?? '—' }}</div></div>
        <div><div class="cur-lbl">Especialidad</div><div class="cur-val">{{ $cita->especialidad->nombre }}</div></div>
        <div><div class="cur-lbl">Fecha y ficha actual</div><div class="cur-val">{{ $cita->fecha->format('d/m/Y') }} · {{ substr($cita->hora_inicio, 0, 5) }}</div></div>
        <div><div class="cur-lbl">Estado</div><div class="cur-val">{{ $cita->estadoCita->nombre }}</div></div>
    </div>

    <form action="{{ route('citas.reprogramar.update', $cita) }}" method="POST" id="formReprogramar">
        @csrf
        @method('PUT')
        <input type="hidden" id="medico_id" value="{{ $cita->medico_id }}">

        <div class="form-section-title">2. Nueva fecha y ficha libre (CU14)</div>
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="fecha">Nueva fecha <span style="color:#ef4444;">*</span></label>
                <input type="date" name="fecha" id="fecha" class="form-control" min="{{ today()->toDateString() }}"
                       value="{{ old('fecha', $cita->fecha->toDateString()) }}" required onchange="consultarFichasDisponibles()">
                @error('fecha') <span class="invalid-feedback">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="form-group full" style="margin-top: 14px;">
            <label class="form-label">Ficha <span style="color:#ef4444;">*</span></label>
            <div class="fichas-container">
                <div id="fichasStatus" style="font-size: 13px; color: #64748b;">Seleccione la nueva fecha para cargar las fichas libres.</div>
                <div id="fichasGrid" class="fichas-grid" style="display: none;"></div>
            </div>
        </div>

        <div class="form-section-title">3. Motivo de la reprogramación</div>
        <div class="form-group full">
            <textarea name="motivo" id="motivo" rows="2" class="form-control" required minlength="5" maxlength="500"
                      placeholder="Ej. Solicitud del paciente, ausencia del médico...">{{ old('motivo') }}</textarea>
            @error('motivo') <span class="invalid-feedback">{{ $message }}</span> @enderror
        </div>

        <div class="form-actions">
            <a href="{{ route('citas.show', $cita) }}" class="btn-cancel">Volver</a>
            <button type="submit" class="btn-save">Confirmar reprogramación</button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    const fichaActualId = {{ (int) $cita->ficha_id }};
    const fechaActual = '{{ $cita->fecha->toDateString() }}';
    const fichaAnterior = '{{ old('ficha_id') }}';

    async function consultarFichasDisponibles() {
        const medicoId = document.getElementById('medico_id').value;
        const fecha = document.getElementById('fecha').value;
        const statusBox = document.getElementById('fichasStatus');
        const grid = document.getElementById('fichasGrid');
        grid.innerHTML = ''; grid.style.display = 'none';
        if (!fecha) { statusBox.innerText = 'Seleccione la nueva fecha.'; return; }
        statusBox.innerText = 'Consultando disponibilidad...';

        try {
            const url = `{{ route('citas.consultarFichas') }}?medico_id=${medicoId}&fecha=${fecha}&excluir_cita_id={{ $cita->id }}`;
            const data = await (await fetch(url)).json();
            if (!data.fichas || data.fichas.length === 0) {
                statusBox.innerText = (data.mensaje || 'El médico no atiende ese día.') + ' Pruebe con otra fecha.';
                return;
            }
            statusBox.innerText = `Jornada del ${data.dia_semana} (${data.horario_inicio} a ${data.horario_fin}). Elija una ficha libre:`;
            grid.style.display = 'grid';

            data.fichas.forEach(f => {
                const esActual = (f.id === fichaActualId && fecha === fechaActual);
                const libre = f.disponible && !esActual;
                const label = document.createElement('label');
                label.className = `ficha-pill ${libre ? '' : 'disabled'} ${esActual ? 'actual' : ''}`;
                label.innerHTML = `
                    <input type="radio" name="ficha_id" value="${f.id}" ${libre ? '' : 'disabled'} onchange="seleccionarFicha(this)">
                    <div class="ficha-time">${f.hora_inicio} - ${f.hora_fin}</div>
                    <div class="ficha-tag" style="color: ${libre ? '#15803d' : '#b91c1c'};">
                        ${esActual ? '● Ficha actual' : (libre ? '✓ Ficha #' + f.numero : '✕ ' + (f.motivo || 'Ocupada'))}
                    </div>`;
                grid.appendChild(label);
                if (libre && String(f.id) === fichaAnterior) {
                    const r = label.querySelector('input'); r.checked = true; seleccionarFicha(r);
                }
            });
        } catch (e) {
            statusBox.innerText = 'Error al consultar disponibilidad. Verifique su conexión.';
        }
    }

    function seleccionarFicha(radio) {
        document.querySelectorAll('.ficha-pill').forEach(p => p.classList.remove('selected'));
        if (radio.checked) radio.closest('.ficha-pill').classList.add('selected');
    }

    document.addEventListener('DOMContentLoaded', consultarFichasDisponibles);
</script>
@endpush
@endsection
