@extends('layouts.app')

@section('title', 'Expediente: ' . $paciente->nombre_completo)

@push('styles')
<style>
    .patient-header {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 24px 28px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 16px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    }
    .patient-title-area {
        display: flex;
        align-items: center;
        gap: 16px;
    }
    .patient-avatar {
        width: 56px;
        height: 56px;
        background: linear-gradient(135deg, #0284c7 0%, #0d9488 100%);
        color: #ffffff;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        font-weight: 700;
    }
    .patient-name {
        font-size: 20px;
        font-weight: 700;
        color: #0f172a;
    }
    .patient-meta {
        font-size: 13px;
        color: #64748b;
        margin-top: 2px;
        display: flex;
        gap: 12px;
    }
    .btn-group {
        display: flex;
        gap: 8px;
    }
    .btn-header {
        padding: 8px 14px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        border: 1px solid transparent;
        cursor: pointer;
    }
    .btn-back { background: #f1f5f9; color: #475569; }
    .btn-edit { background: #fef3c7; color: #92400e; border-color: #fde68a; }
    .btn-deactivate { background: #fee2e2; color: #b91c1c; border-color: #fecaca; }
    .btn-activate { background: #dcfce7; color: #15803d; border-color: #bbf7d0; }

    /* Paneles de datos */
    .details-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 20px;
        margin-bottom: 28px;
    }
    .detail-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 22px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    }
    .detail-card-title {
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #0284c7;
        margin-bottom: 16px;
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 8px;
    }
    .detail-list {
        display: grid;
        gap: 10px;
        font-size: 13.5px;
    }
    .detail-row {
        display: flex;
        justify-content: space-between;
    }
    .detail-label {
        color: #64748b;
        font-weight: 500;
    }
    .detail-value {
        color: #0f172a;
        font-weight: 600;
        text-align: right;
    }

    /* Seccion de Historial de Citas */
    .history-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 24px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    }
    .history-title {
        font-size: 16px;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 4px;
    }
    .history-desc {
        font-size: 13px;
        color: #64748b;
        margin-bottom: 18px;
    }
    .history-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
        text-align: left;
    }
    .history-table th {
        background: #f8fafc;
        color: #475569;
        font-weight: 700;
        padding: 11px 14px;
        border-bottom: 1px solid #e2e8f0;
        font-size: 11.5px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .history-table td {
        padding: 12px 14px;
        border-bottom: 1px solid #f1f5f9;
        color: #334155;
    }
    /* Estados de Cita */
    .badge-estado {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 700;
    }
    .estado-programada { background: #e0f2fe; color: #0369a1; }
    .estado-confirmada { background: #dbeafe; color: #1d4ed8; }
    .estado-atendida { background: #dcfce7; color: #15803d; }
    .estado-cancelada { background: #fee2e2; color: #b91c1c; }
    .estado-inasistencia { background: #fef3c7; color: #92400e; }
</style>
@endpush

@section('content')

<!-- Cabecera del Expediente -->
<div class="patient-header">
    <div class="patient-title-area">
        <div class="patient-avatar">
            {{ strtoupper(substr($paciente->persona->nombres ?? 'P', 0, 1)) }}
        </div>
        <div>
            <h1 class="patient-name">{{ $paciente->nombre_completo }}</h1>
            <div class="patient-meta">
                <span><strong>Código:</strong> {{ $paciente->codigo }}</span>
                <span>•</span>
                <span><strong>CI:</strong> {{ $paciente->persona->ci ?? '—' }}</span>
                <span>•</span>
                <span style="font-weight: 700; color: {{ $paciente->estado ? '#15803d' : '#b91c1c' }};">
                    {{ $paciente->estado ? '● Activo' : '○ Inactivo' }}
                </span>
            </div>
        </div>
    </div>
    <div class="btn-group">
        <a href="{{ route('pacientes.index') }}" class="btn-header btn-back">&larr; Volver al Listado</a>
        @if(in_array(Auth::user()->rol->nombre ?? '', ['Administrador', 'Recepcionista']))
            <a href="{{ route('pacientes.edit', $paciente) }}" class="btn-header btn-edit">Modificar</a>
            <form action="{{ route('pacientes.toggleEstado', $paciente) }}" method="POST" style="display: inline;" onsubmit="return confirm('¿Está seguro de cambiar el estado de este paciente?');">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn-header {{ $paciente->estado ? 'btn-deactivate' : 'btn-activate' }}">
                    {{ $paciente->estado ? 'Desactivar' : 'Activar' }}
                </button>
            </form>
        @endif
    </div>
</div>

<!-- Grilla de Informacion del Expediente -->
<div class="details-grid">
    <!-- 1. Informacion Personal -->
    <div class="detail-card">
        <div class="detail-card-title">1. Información Personal y Demográfica</div>
        <div class="detail-list">
            <div class="detail-row">
                <span class="detail-label">Fecha de Nacimiento:</span>
                <span class="detail-value">
                    {{ $paciente->fecha_nacimiento ? $paciente->fecha_nacimiento->format('d/m/Y') : '—' }}
                    @if($paciente->fecha_nacimiento)
                        ({{ \Carbon\Carbon::parse($paciente->fecha_nacimiento)->age }} años)
                    @endif
                </span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Sexo:</span>
                <span class="detail-value">{{ $paciente->sexo === 'M' ? 'Masculino' : 'Femenino' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Teléfono / Celular:</span>
                <span class="detail-value">{{ $paciente->persona->telefono ?? 'Sin registrar' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Correo Electrónico:</span>
                <span class="detail-value">{{ $paciente->persona->correo ?? 'Sin registrar' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Dirección:</span>
                <span class="detail-value">{{ $paciente->direccion ?? 'Sin registrar' }}</span>
            </div>
        </div>
    </div>

    <!-- 2. Informacion Clinica Base -->
    <div class="detail-card">
        <div class="detail-card-title">2. Información Médica y Alergias</div>
        <div class="detail-list">
            <div class="detail-row">
                <span class="detail-label">Tipo de Sangre:</span>
                <span class="detail-value" style="font-size: 16px; color: #0284c7;">{{ $paciente->tipo_sangre ?? 'No especificado' }}</span>
            </div>
            <div class="detail-row" style="flex-direction: column; gap: 4px;">
                <span class="detail-label">Alergias y Observaciones Clínicas:</span>
                <span class="detail-value" style="text-align: left; font-weight: normal; color: #475569; background: #f8fafc; padding: 8px 10px; border-radius: 6px; margin-top: 4px;">
                    {{ $paciente->otras_alergias ?: 'Sin alergias registradas.' }}
                </span>
            </div>
        </div>
    </div>

    <!-- 3. Contacto de Emergencia -->
    <div class="detail-card">
        <div class="detail-card-title">3. Contacto de Emergencia</div>
        <div class="detail-list">
            <div class="detail-row">
                <span class="detail-label">Nombre del Contacto:</span>
                <span class="detail-value">{{ $paciente->contacto_emerg_nombre ?? 'Sin registrar' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Parentesco:</span>
                <span class="detail-value">{{ $paciente->contacto_emerg_parentesco ?? '—' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Teléfono de Contacto:</span>
                <span class="detail-value" style="color: #0284c7;">{{ $paciente->contacto_emerg_telefono ?? '—' }}</span>
            </div>
        </div>
    </div>
</div>

<!-- Seccion de Historial de Citas Medicas (RF04 / RF12 / CU08) -->
<div class="history-card">
    <h2 class="history-title">Historial Completo de Citas Médicas</h2>
    <p class="history-desc">Trazabilidad histórica de citas programadas, confirmadas, atendidas y canceladas (RN10 / RN11).</p>

    <div style="overflow-x: auto;">
        <table class="history-table">
            <thead>
                <tr>
                    <th>Código Cita</th>
                    <th>Fecha</th>
                    <th>Horario / Ficha</th>
                    <th>Especialidad</th>
                    <th>Médico</th>
                    <th>Modalidad</th>
                    <th>Estado</th>
                    <th>Motivo de Consulta</th>
                </tr>
            </thead>
            <tbody>
                @forelse($paciente->citas as $cita)
                    @php
                        $estadoNom = strtolower($cita->estadoCita->nombre ?? 'programada');
                        $badgeClass = match($estadoNom) {
                            'programada' => 'estado-programada',
                            'confirmada' => 'estado-confirmada',
                            'atendida' => 'estado-atendida',
                            'cancelada' => 'estado-cancelada',
                            'inasistencia' => 'estado-inasistencia',
                            default => 'estado-programada'
                        };
                    @endphp
                    <tr>
                        <td><strong>{{ $cita->codigo }}</strong></td>
                        <td>{{ \Carbon\Carbon::parse($cita->fecha)->format('d/m/Y') }}</td>
                        <td>
                            {{ substr($cita->hora_inicio, 0, 5) }} - {{ substr($cita->hora_fin, 0, 5) }}
                            @if($cita->ficha)
                                <span style="font-size: 11px; color: #64748b;">(Ficha #{{ $cita->ficha->numero }})</span>
                            @endif
                        </td>
                        <td>{{ $cita->especialidad->nombre ?? '—' }}</td>
                        <td><strong>{{ $cita->medico->persona->nombre_completo ?? '—' }}</strong></td>
                        <td>{{ $cita->modalidad }}</td>
                        <td>
                            <span class="badge-estado {{ $badgeClass }}">
                                {{ $cita->estadoCita->nombre ?? '—' }}
                            </span>
                        </td>
                        <td style="max-width: 200px;">
                            {{ $cita->motivo ?: 'Sin motivo registrado' }}
                            @if($cita->motivo_cancelacion)
                                <div style="font-size: 11px; color: #b91c1c; margin-top: 2px;">
                                    <strong>Cancelación:</strong> {{ $cita->motivo_cancelacion }}
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 24px; color: #64748b;">
                            El paciente aún no cuenta con citas médicas registradas en el sistema.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
