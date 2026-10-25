@extends('layouts.app')

@section('title', 'Agendar Nueva Cita Médica')

@push('styles')
<style>
    .booking-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 28px 32px; max-width: 860px; margin: 0 auto; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
    .booking-title { font-size: 20px; font-weight: 700; color: #0f172a; margin-bottom: 4px; }
    .booking-desc { font-size: 13px; color: #64748b; margin-bottom: 24px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; }
    .form-section-title { font-size: 13.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #0284c7; margin: 20px 0 12px; display: flex; align-items: center; gap: 8px; }
    .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px 20px; }
    .form-group { display: flex; flex-direction: column; gap: 6px; }
    .form-group.full { grid-column: 1 / -1; }
    .form-label { font-size: 12.5px; font-weight: 600; color: #334155; }
    .form-control { padding: 9px 13px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; outline: none; background: #fff; }
    .form-control:focus { border-color: #0284c7; }
    .invalid-feedback { font-size: 11.5px; color: #dc2626; margin-top: 2px; }

    /* Contenedor de Fichas Dinamicas */
    .fichas-container { background: #f8fafc; border: 1.5px dashed #cbd5e1; border-radius: 12px; padding: 18px; margin-top: 6px; }
    .fichas-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 10px; margin-top: 10px; }
    .ficha-pill { border: 1.5px solid #cbd5e1; background: #fff; border-radius: 8px; padding: 10px; text-align: center; cursor: pointer; transition: all 0.15s; }
    .ficha-pill:hover:not(.disabled) { border-color: #0284c7; background: #f0fdf4; }
    .ficha-pill input { display: none; }
    .ficha-pill.selected { border-color: #0284c7; background: #e0f2fe; box-shadow: 0 0 0 2px #0284c7; }
    .ficha-pill.disabled { opacity: 0.5; background: #e2e8f0; cursor: not-allowed; border-color: #cbd5e1; }
    .ficha-time { font-size: 13px; font-weight: 700; color: #0f172a; }
    .ficha-tag { font-size: 10.5px; font-weight: 600; margin-top: 2px; }

    .form-actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 28px; padding-top: 18px; border-top: 1px solid #f1f5f9; }
    .btn-save { background: linear-gradient(135deg, #0284c7 0%, #0d9488 100%); color: #fff; padding: 10px 24px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 14px; }
    .btn-cancel { background: #f1f5f9; color: #475569; padding: 10px 18px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 13.5px; }
</style>
@endpush

@section('content')

<div class="booking-card">
    <h1 class="booking-title">Agendar Nueva Cita Médica</h1>
    <p class="booking-desc">Seleccione el paciente, especialidad, médico y un turno libre para reservar la consulta (RF06 / CU15).</p>

    <form action="{{ route('citas.store') }}" method="POST" id="formCita">
        @csrf

        <!-- 1. Seleccion de Paciente -->
        <div class="form-section-title">1. Selección del Paciente</div>
        <div class="form-group full">
            <label class="form-label" for="paciente_id">Paciente <span style="color: #ef4444;">*</span></label>
            <select name="paciente_id" id="paciente_id" class="form-control @error('paciente_id') is-invalid @enderror" required>
                <option value="">Seleccione al paciente...</option>
                @foreach($pacientes as $pac)
                    <option value="{{ $pac->id }}" {{ old('paciente_id', $pacientePre) == $pac->id ? 'selected' : '' }}>
                        {{ $pac->nombre_completo }} — CI: {{ $pac->persona->ci ?? 'S/CI' }} (Código: {{ $pac->codigo }})
                    </option>
                @endforeach
            </select>
            @error('paciente_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
        </div>

        <!-- 2. Seleccion de Especialidad y Medico -->
        <div class="form-section-title">2. Especialidad Médica y Facultativo</div>
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="especialidad_id">Especialidad <span style="color: #ef4444;">*</span></label>
                <select name="especialidad_id" id="especialidad_id" class="form-control" required onchange="filtrarMedicosPorEspecialidad()">
                    <option value="">Seleccione especialidad...</option>
                    @foreach($especialidades as $esp)
                        <option value="{{ $esp->id }}" {{ old('especialidad_id', $especialidadPre) == $esp->id ? 'selected' : '' }}>
                            {{ $esp->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="medico_id">Médico Asignado <span style="color: #ef4444;">*</span></label>
                <select name="medico_id" id="medico_id" class="form-control @error('medico_id') is-invalid @enderror" required onchange="consultarFichasDisponibles()">
                    <option value="">Seleccione primero la especialidad...</option>
                    @foreach($medicos as $med)
                        @php
                            $espIds = $med->especialidades->pluck('id')->join(',');
                        @endphp
                        <option value="{{ $med->id }}" data-especialidades="{{ $espIds }}" {{ old('medico_id', $medicoPre) == $med->id ? 'selected' : '' }}>
                            Dr(a). {{ $med->persona->nombre_completo ?? $med->codigo }}
                        </option>
                    @endforeach
                </select>
                @error('medico_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
            </div>
        </div>

        <!-- 3. Seleccion de Fecha y Fichas Disponibles (CU14) -->
        <div class="form-section-title">3. Fecha de Atención y Consulta de Turnos Libres (CU14)</div>
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="fecha">Fecha de la Cita <span style="color: #ef4444;">*</span></label>
                <input 
                    type="date" 
                    name="fecha" 
                    id="fecha" 
                    class="form-control @error('fecha') is-invalid @enderror" 
                    min="{{ today()->toDateString() }}" 
                    value="{{ old('fecha', today()->toDateString()) }}" 
                    required 
                    onchange="consultarFichasDisponibles()"
                >
                @error('fecha') <span class="invalid-feedback">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="modalidad">Modalidad de Consulta <span style="color: #ef4444;">*</span></label>
                <select name="modalidad" id="modalidad" class="form-control" required>
                    <option value="Presencial" {{ old('modalidad') === 'Presencial' ? 'selected' : '' }}>🏥 Presencial (En consultorio)</option>
                    <option value="Virtual" {{ old('modalidad') === 'Virtual' ? 'selected' : '' }}>💻 Telemedicina (Virtual)</option>
                </select>
            </div>
        </div>

        <!-- Contenedor reactivo de Fichas (CU14) -->
        <div class="form-group full" style="margin-top: 14px;">
            <label class="form-label">Turno / Ficha Predeterminada (30 minutos) <span style="color: #ef4444;">*</span></label>
            <div class="fichas-container">
                <div id="fichasStatus" style="font-size: 13px; color: #64748b;">
                    Seleccione un médico y una fecha válida para cargar los turnos disponibles.
                </div>
                <div id="fichasGrid" class="fichas-grid" style="display: none;"></div>
            </div>
            @error('ficha_id') <span class="invalid-feedback" style="display: block;">{{ $message }}</span> @enderror
        </div>

        <!-- 4. Motivo de la Cita -->
        <div class="form-section-title">4. Motivo de Consulta</div>
        <div class="form-group full">
            <label class="form-label" for="motivo">Motivo breve o síntomas principales</label>
            <textarea name="motivo" id="motivo" rows="2" class="form-control" placeholder="Ej. Dolor abdominal agudo, revisión de rutina, control de presión...">{{ old('motivo') }}</textarea>
        </div>

        <div class="form-actions">
            <a href="{{ route('citas.index') }}" class="btn-cancel">Cancelar</a>
            <button type="submit" class="btn-save">Confirmar y Agendar Cita</button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    // Filtrar médicos según la especialidad seleccionada
    function filtrarMedicosPorEspecialidad() {
        const espSelect = document.getElementById('especialidad_id');
        const medSelect = document.getElementById('medico_id');
        const espId = espSelect.value;

        Array.from(medSelect.options).forEach(opt => {
            if (!opt.value) return;
            const espIds = opt.getAttribute('data-especialidades') || '';
            const list = espIds.split(',');
            if (!espId || list.includes(espId)) {
                opt.style.display = '';
            } else {
                opt.style.display = 'none';
                if (opt.selected) opt.selected = false;
            }
        });

        consultarFichasDisponibles();
    }

    // Consulta AJAX de disponibilidad de turnos en vivo (CU14)
    async function consultarFichasDisponibles() {
        const medicoId = document.getElementById('medico_id').value;
        const fecha = document.getElementById('fecha').value;
        const statusBox = document.getElementById('fichasStatus');
        const grid = document.getElementById('fichasGrid');

        if (!medicoId || !fecha) {
            statusBox.style.display = 'block';
            statusBox.innerText = 'Seleccione un médico y una fecha para cargar los turnos.';
            grid.style.display = 'none';
            grid.innerHTML = '';
            return;
        }

        statusBox.style.display = 'block';
        statusBox.innerText = 'Consultando disponibilidad de turnos...';
        grid.style.display = 'none';
        grid.innerHTML = '';

        try {
            const resp = await fetch(`{{ route('citas.consultarFichas') }}?medico_id=${medicoId}&fecha=${fecha}`);
            const data = await resp.json();

            if (!data.fichas || data.fichas.length === 0) {
                statusBox.innerText = data.mensaje || 'El médico no tiene jornada de atención para el día seleccionado.';
                return;
            }

            statusBox.innerText = `Jornada del día ${data.dia_semana} (${data.horario_inicio} a ${data.horario_fin}). Seleccione un turno disponible:`;
            grid.style.display = 'grid';

            data.fichas.forEach(f => {
                const label = document.createElement('label');
                label.className = `ficha-pill ${f.disponible ? '' : 'disabled'}`;

                label.innerHTML = `
                    <input type="radio" name="ficha_id" value="${f.id}" ${f.disponible ? '' : 'disabled'} onchange="seleccionarFicha(this)">
                    <div class="ficha-time">${f.hora_inicio} - ${f.hora_fin}</div>
                    <div class="ficha-tag" style="color: ${f.disponible ? '#15803d' : '#b91c1c'};">
                        ${f.disponible ? '✓ Ficha #' + f.numero : '✕ Ocupada'}
                    </div>
                `;
                grid.appendChild(label);
            });
        } catch (e) {
            statusBox.innerText = 'Error al consultar disponibilidad. Verifique su conexión.';
        }
    }

    function seleccionarFicha(radio) {
        document.querySelectorAll('.ficha-pill').forEach(p => p.classList.remove('selected'));
        if (radio.checked) {
            radio.closest('.ficha-pill').classList.add('selected');
        }
    }

    // Inicializar al cargar
    document.addEventListener('DOMContentLoaded', () => {
        filtrarMedicosPorEspecialidad();
    });
</script>
@endpush

@endsection
