@extends('layouts.app')

@section('title', 'Perfil: Dr(a). ' . $medico->nombre_completo)

@push('styles')
<style>
    .profile-header { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 24px 28px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
    .profile-title-area { display: flex; align-items: center; gap: 16px; }
    .profile-avatar { width: 56px; height: 56px; background: linear-gradient(135deg, #0284c7 0%, #0d9488 100%); color: #ffffff; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 22px; font-weight: 700; }
    .profile-name { font-size: 20px; font-weight: 700; color: #0f172a; }
    .profile-meta { font-size: 13px; color: #64748b; margin-top: 2px; display: flex; gap: 12px; }
    .btn-group { display: flex; gap: 8px; }
    .btn-header { padding: 8px 14px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; border: 1px solid transparent; cursor: pointer; }
    .btn-back { background: #f1f5f9; color: #475569; }
    .btn-edit { background: #fef3c7; color: #92400e; border-color: #fde68a; }
    .btn-schedule { background: #e0f2fe; color: #0369a1; border-color: #bae6fd; }

    /* Secciones de Contenido */
    .section-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 24px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
    .section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
    .section-title { font-size: 16px; font-weight: 700; color: #0f172a; }
    .badge-esp { display: inline-block; background-color: #e0f2fe; color: #0369a1; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; margin-right: 6px; }
    .table { width: 100%; border-collapse: collapse; font-size: 13px; text-align: left; }
    .table th { background: #f8fafc; color: #475569; padding: 11px 14px; border-bottom: 1px solid #e2e8f0; font-size: 11.5px; text-transform: uppercase; }
    .table td { padding: 12px 14px; border-bottom: 1px solid #f1f5f9; }
</style>
@endpush

@section('content')

<!-- Cabecera de Perfil -->
<div class="profile-header">
    <div class="profile-title-area">
        <div class="profile-avatar">
            🩺
        </div>
        <div>
            <h1 class="profile-name">Dr(a). {{ $medico->nombre_completo }}</h1>
            <div class="profile-meta">
                <span><strong>Código:</strong> {{ $medico->codigo }}</span>
                <span>•</span>
                <span><strong>Reg. Profesional:</strong> <code>{{ $medico->registro_profesional }}</code></span>
                <span>•</span>
                <span style="font-weight: 700; color: {{ $medico->estado ? '#15803d' : '#b91c1c' }};">
                    {{ $medico->estado ? '● Habilitado' : '○ Inhabilitado (RN04)' }}
                </span>
            </div>
        </div>
    </div>
    <div class="btn-group">
        <a href="{{ route('medicos.index') }}" class="btn-header btn-back">&larr; Volver al Listado</a>
        @if(Auth::user()->rol->nombre === 'Administrador')
            <a href="{{ route('horarios.create', ['medico_id' => $medico->id]) }}" class="btn-header btn-schedule">+ Asignar Horario</a>
            <a href="{{ route('medicos.edit', $medico) }}" class="btn-header btn-edit">Modificar</a>
        @endif
    </div>
</div>

<!-- 1. Especialidades Asignadas -->
<div class="section-card">
    <h2 class="section-title" style="margin-bottom: 12px;">Especialidades Médicas Asignadas</h2>
    <div>
        @forelse($medico->especialidades as $esp)
            <span class="badge-esp">{{ $esp->nombre }}</span>
        @empty
            <span style="color: #94a3b8; font-size: 13px;">Sin especialidades asignadas.</span>
        @endforelse
    </div>
</div>

<!-- 2. Horarios de Atencion Semanales y Fichas (RF05) -->
<div class="section-card">
    <div class="section-header">
        <div>
            <h2 class="section-title">Horarios de Atención y Fichas de Consulta (RF05 / CU13)</h2>
            <p style="font-size: 13px; color: #64748b; margin-top: 2px;">Jornadas semanales disponibles y cálculo automático de bloques de atención.</p>
        </div>
        @if(Auth::user()->rol->nombre === 'Administrador')
            <a href="{{ route('horarios.create', ['medico_id' => $medico->id]) }}" style="font-size: 13px; font-weight: 600; color: #0284c7; text-decoration: none;">+ Agregar Día</a>
        @endif
    </div>

    <div style="overflow-x: auto;">
        <table class="table">
            <thead>
                <tr>
                    <th>Día de la Semana</th>
                    <th>Hora Inicio</th>
                    <th>Hora Fin</th>
                    <th>Duración Cita</th>
                    <th>Fichas Calculadas</th>
                    <th>Estado</th>
                    <th style="text-align: right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($medico->horarios as $horario)
                    <tr>
                        <td><strong>{{ $horario->dia_semana }}</strong></td>
                        <td>{{ substr($horario->hora_inicio, 0, 5) }}</td>
                        <td>{{ substr($horario->hora_fin, 0, 5) }}</td>
                        <td>{{ $horario->duracion_cita_min }} minutos</td>
                        <td>
                            <strong style="color: #0284c7;">{{ $horario->fichas->count() }} fichas</strong>
                        </td>
                        <td>
                            <span style="font-size: 11px; font-weight: 700; color: {{ $horario->estado ? '#15803d' : '#b91c1c' }};">
                                {{ $horario->estado ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td style="text-align: right;">
                            <a href="{{ route('horarios.show', $horario) }}" style="color: #0284c7; font-weight: 600; text-decoration: none;">
                                Ver Fichas &rarr;
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 24px; color: #64748b;">
                            El médico aún no tiene horarios de atención configurados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- 3. Citas Medicas Asignadas -->
<div class="section-card">
    <h2 class="section-title" style="margin-bottom: 4px;">Últimas Citas Médicas Asignadas</h2>
    <p style="font-size: 13px; color: #64748b; margin-bottom: 16px;">Trazabilidad clínica de las consultas programadas y atendidas por este facultativo.</p>

    <div style="overflow-x: auto;">
        <table class="table">
            <thead>
                <tr>
                    <th>Código Cita</th>
                    <th>Fecha</th>
                    <th>Horario / Ficha</th>
                    <th>Paciente</th>
                    <th>Especialidad</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse($medico->citas as $cita)
                    <tr>
                        <td><strong>{{ $cita->codigo }}</strong></td>
                        <td>{{ \Carbon\Carbon::parse($cita->fecha)->format('d/m/Y') }}</td>
                        <td>{{ substr($cita->hora_inicio, 0, 5) }} - {{ substr($cita->hora_fin, 0, 5) }} (Ficha #{{ $cita->ficha->numero ?? '—' }})</td>
                        <td><strong>{{ $cita->paciente->persona->nombre_completo ?? '—' }}</strong></td>
                        <td>{{ $cita->especialidad->nombre ?? '—' }}</td>
                        <td>{{ $cita->estadoCita->nombre ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 20px; color: #64748b;">
                            No registra citas médicas en el historial.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
