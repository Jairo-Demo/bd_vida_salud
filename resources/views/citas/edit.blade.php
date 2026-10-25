@extends('layouts.app')

@section('title', 'Reprogramar Cita: ' . $cita->codigo)

@push('styles')
<style>
    .form-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 28px 32px; max-width: 760px; margin: 0 auto; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
    .form-title { font-size: 20px; font-weight: 700; color: #0f172a; margin-bottom: 4px; }
    .form-desc { font-size: 13px; color: #64748b; margin-bottom: 22px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; }
    .current-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px 18px; margin-bottom: 20px; font-size: 13.5px; }
    .form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 18px; }
    .form-label { font-size: 12.5px; font-weight: 600; color: #334155; }
    .form-control { padding: 9px 13px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; outline: none; background: #fff; }
    .form-control:focus { border-color: #0284c7; }
    .invalid-feedback { font-size: 11.5px; color: #dc2626; margin-top: 2px; }

    /* Contenedor Fichas */
    .fichas-container { background: #f8fafc; border: 1.5px dashed #cbd5e1; border-radius: 12px; padding: 16px; margin-top: 6px; }
    .fichas-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 10px; margin-top: 10px; }
    .ficha-pill { border: 1.5px solid #cbd5e1; background: #fff; border-radius: 8px; padding: 10px; text-align: center; cursor: pointer; }
    .ficha-pill.selected { border-color: #0284c7; background: #e0f2fe; box-shadow: 0 0 0 2px #0284c7; }
    .ficha-pill.disabled { opacity: 0.5; background: #e2e8f0; cursor: not-allowed; }
    .ficha-pill input { display: none; }
    .ficha-time { font-size: 13px; font-weight: 700; color: #0f172a; }

    .form-actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 26px; padding-top: 18px; border-top: 1px solid #f1f5f9; }
    .btn-save { background: linear-gradient(135deg, #0284c7 0%, #0d9488 100%); color: #fff; padding: 10px 22px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; }
    .btn-cancel { background: #f1f5f9; color: #475569; padding: 10px 18px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 13.5px; }
</style>
@endpush

@section('content')

<div class="form-card">
    <h1 class="form-title">Reprogramar Cita Médica</h1>
    <p class="form-desc">Cambio de fecha y asignación de nuevo turno de atención (RF08 / CU15).</p>

    <!-- Resumen de cita actual -->
    <div class="current-box">
        <div><strong>Cita:</strong> {{ $cita->codigo }} | <strong>Estado:</strong> {{ $cita->estadoCita->nombre }}</div>
        <div style="margin-top: 4px;"><strong>Paciente:</strong> {{ $cita->paciente->nombre_completo }}</div>
        <div><strong>Médico:</strong> Dr(a). {{ $cita->medico->persona->nombre_completo ?? '—' }} ({{ $cita->especialidad->nombre }})</div>
        <div style="margin-top: 4px; color: #b91c1c;">
            <strong>Fecha/Horario actual:</strong> {{ \Carbon\Carbon::parse($cita->fecha)->format('d/m/Y') }} a las {{ substr($cita->hora_inicio, 0, 5) }} (Ficha #{{ $cita->ficha->numero ?? '—' }})
        </div>
    </div>

    <form action="{{ route('citas.update', $cita) }}" method="POST">
        @csrf
        @method('PUT')

        <input type="hidden" id="medico_id" value="{{ $cita->medico_id }}">

        <div class="form-group">
            <label class="form-label" for="fecha">Nueva Fecha de Consulta <span style="color: #ef4444;">*</span></label>
            <input 
                type="date" 
                name="fecha" 
                id="fecha" 
                class="form-control @error('fecha') is-invalid @enderror" 
                min="{{ today()->toDateString() }}" 
                value="{{ old('fecha', \Carbon\Carbon::parse($cita->fecha)->format('Y-m-d')) }}" 
                required 
                onchange="consultarFichas()"
            >
            @error('fecha') <span class="invalid-feedback">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label class="form-label">Seleccione el Nuevo Turno / Ficha <span style="color: #ef4444;">*</span></label>
            <div class="fichas-container">
                <div id="fichasStatus" style="font-size: 13px; color: #64748b;">
                    Cargando disponibilidad para la fecha...
                </div>
                <div id="fichasGrid" class="fichas-grid" style="display: none;"></div>
            </div>
            @error('ficha_id') <span class="invalid-feedback" style="display: block;">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="motivo_reprogramacion">Motivo de la Reprogramación <span style="color: #ef4444;">*</span></label>
            <input type="text" name="motivo_reprogramacion" id="motivo_reprogramacion" class="form-control @error('motivo_reprogramacion') is-invalid @enderror" placeholder="Ej. Solicitud del paciente por viaje, cruce de horario laboral..." value="{{ old('motivo_reprogramacion') }}" required>
            @error('motivo_reprogramacion') <span class="invalid-feedback">{{ $message }}</span> @enderror
        </div>

        <div class="form-actions">
            <a href="{{ route('citas.show', $cita) }}" class="btn-cancel">Cancelar</a>
            <button type="submit" class="btn-save">Confirmar Reprogramación</button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    async function consultarFichas() {
        const medicoId = document.getElementById('medico_id').value;
        const fecha = document.getElementById('fecha').value;
        const statusBox = document.getElementById('fichasStatus');
        const grid = document.getElementById('fichasGrid');

        statusBox.style.display = 'block';
        statusBox.innerText = 'Consultando turnos disponibles...';
        grid.style.display = 'none';
        grid.innerHTML = '';

        try {
            const resp = await fetch(`{{ route('citas.consultarFichas') }}?medico_id=${medicoId}&fecha=${fecha}`);
            const data = await resp.json();

            if (!data.fichas || data.fichas.length === 0) {
                statusBox.innerText = data.mensaje || 'Sin turnos para la fecha.';
                return;
            }

            statusBox.innerText = `Jornada del día ${data.dia_semana}. Seleccione el nuevo turno:`;
            grid.style.display = 'grid';

            data.fichas.forEach(f => {
                const label = document.createElement('label');
                label.className = `ficha-pill ${f.disponible ? '' : 'disabled'}`;

                label.innerHTML = `
                    <input type="radio" name="ficha_id" value="${f.id}" ${f.disponible ? '' : 'disabled'} onchange="seleccionarFicha(this)">
                    <div class="ficha-time">${f.hora_inicio} - ${f.hora_fin}</div>
                    <div style="font-size: 10.5px; color: ${f.disponible ? '#15803d' : '#b91c1c'}; font-weight: 600;">
                        ${f.disponible ? '✓ Ficha #' + f.numero : '✕ Ocupada'}
                    </div>
                `;
                grid.appendChild(label);
            });
        } catch (e) {
            statusBox.innerText = 'Error al consultar disponibilidad.';
        }
    }

    function seleccionarFicha(radio) {
        document.querySelectorAll('.ficha-pill').forEach(p => p.classList.remove('selected'));
        if (radio.checked) {
            radio.closest('.ficha-pill').classList.add('selected');
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        consultarFichas();
    });
</script>
@endpush

@endsection
