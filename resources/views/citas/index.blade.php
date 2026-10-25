@extends('layouts.app')

@section('title', 'Agenda y Control de Citas Médicas')

@push('styles')
<style>
    .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; }
    .page-title { font-size: 22px; font-weight: 700; color: #0f172a; }
    .page-subtitle { font-size: 13px; color: #64748b; margin-top: 2px; }
    .btn-create { background: linear-gradient(135deg, #0284c7 0%, #0d9488 100%); color: #ffffff; padding: 9px 18px; border-radius: 9px; text-decoration: none; font-size: 13.5px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25); }

    /* Filtros de la Agenda */
    .agenda-filters { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px 20px; margin-bottom: 24px; }
    .filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; align-items: end; }
    .filter-label { font-size: 11.5px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 4px; display: block; }
    .filter-control { width: 100%; padding: 8px 12px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 13px; outline: none; background: #fff; }
    .filter-control:focus { border-color: #0284c7; }
    .btn-submit { background: #0f172a; color: #fff; padding: 9px 16px; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; height: 38px; }
    .btn-reset { background: #f1f5f9; color: #475569; padding: 9px 14px; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 600; text-align: center; height: 38px; display: inline-flex; align-items: center; justify-content: center; }

    /* Tabla */
    .table-container { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; overflow-x: auto; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
    .data-table { width: 100%; border-collapse: collapse; font-size: 13.5px; text-align: left; }
    .data-table th { background: #f8fafc; color: #475569; font-weight: 700; padding: 12px 16px; border-bottom: 1px solid #e2e8f0; font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.5px; }
    .data-table td { padding: 13px 16px; border-bottom: 1px solid #f1f5f9; color: #334155; }
    .data-table tr:hover { background-color: #f8fafc; }

    /* Estados de Cita */
    .badge-estado { display: inline-block; padding: 3px 9px; border-radius: 6px; font-size: 11px; font-weight: 700; }
    .estado-programada { background: #e0f2fe; color: #0369a1; }
    .estado-confirmada { background: #dbeafe; color: #1d4ed8; }
    .estado-atendida { background: #dcfce7; color: #15803d; }
    .estado-cancelada { background: #fee2e2; color: #b91c1c; }
    .estado-inasistencia { background: #fef3c7; color: #92400e; }

    .btn-detail { background-color: #e0f2fe; color: #0369a1; padding: 5px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; text-decoration: none; }
    .btn-detail:hover { background-color: #bae6fd; }
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">Agenda Médica y Gestión de Citas (P4)</h1>
        <p class="page-subtitle">Programación de turnos, verificación anti-solapamiento y control de asistencia (RF06 - RF12 / CU15 - CU17)</p>
    </div>
    @if(in_array(Auth::user()->rol->nombre ?? '', ['Administrador', 'Recepcionista', 'Paciente']))
        <a href="{{ route('citas.create') }}" class="btn-create">
            <span>+</span> Agendar Nueva Cita
        </a>
    @endif
</div>

<!-- Filtros de la Agenda Diaria -->
<div class="agenda-filters">
    <form action="{{ route('citas.index') }}" method="GET" class="filter-grid">
        <div>
            <label class="filter-label">Fecha de la Cita</label>
            <input type="date" name="fecha" value="{{ $fecha }}" class="filter-control">
        </div>

        @if(Auth::user()->rol->nombre !== 'Medico')
            <div>
                <label class="filter-label">Médico</label>
                <select name="medico_id" class="filter-control">
                    <option value="">Todos los Médicos</option>
                    @foreach($medicos as $med)
                        <option value="{{ $med->id }}" {{ $medicoId == $med->id ? 'selected' : '' }}>
                            Dr(a). {{ $med->persona->nombre_completo ?? $med->codigo }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif

        <div>
            <label class="filter-label">Especialidad</label>
            <select name="especialidad_id" class="filter-control">
                <option value="">Todas</option>
                @foreach($especialidades as $esp)
                    <option value="{{ $esp->id }}" {{ $especialidadId == $esp->id ? 'selected' : '' }}>
                        {{ $esp->nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="filter-label">Estado</label>
            <select name="estado_id" class="filter-control">
                <option value="">Todos los Estados</option>
                @foreach($estados as $est)
                    <option value="{{ $est->id }}" {{ $estadoId == $est->id ? 'selected' : '' }}>
                        {{ $est->nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="filter-label">Buscar Paciente / Código</label>
            <input type="text" name="buscar" value="{{ $buscar }}" class="filter-control" placeholder="CI o Nombre...">
        </div>

        <div style="display: flex; gap: 8px;">
            <button type="submit" class="btn-submit" style="flex: 1;">Filtrar</button>
            <a href="{{ route('citas.index') }}" class="btn-reset">Hoy</a>
        </div>
    </form>
</div>

<!-- Tabla de Citas -->
<div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th>Código</th>
                <th>Fecha / Turno</th>
                <th>Paciente</th>
                <th>Médico</th>
                <th>Especialidad</th>
                <th>Modalidad</th>
                <th>Estado</th>
                <th style="text-align: right;">Acción</th>
            </tr>
        </thead>
        <tbody>
            @forelse($citas as $cita)
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
                    <td>
                        <strong>{{ \Carbon\Carbon::parse($cita->fecha)->format('d/m/Y') }}</strong>
                        <div style="font-size: 12px; color: #64748b;">
                            {{ substr($cita->hora_inicio, 0, 5) }} - {{ substr($cita->hora_fin, 0, 5) }}
                            @if($cita->ficha)
                                <span style="color: #0284c7; font-weight: 600;">(Ficha #{{ $cita->ficha->numero }})</span>
                            @endif
                        </div>
                    </td>
                    <td>
                        <strong>{{ $cita->paciente->persona->nombre_completo ?? '—' }}</strong>
                        <div style="font-size: 11.5px; color: #64748b;">CI: {{ $cita->paciente->persona->ci ?? '—' }}</div>
                    </td>
                    <td>Dr(a). {{ $cita->medico->persona->nombre_completo ?? '—' }}</td>
                    <td>{{ $cita->especialidad->nombre ?? '—' }}</td>
                    <td>
                        <span style="font-size: 12px; font-weight: 600;">
                            {{ $cita->modalidad === 'Virtual' ? '💻 Virtual' : '🏥 Presencial' }}
                        </span>
                    </td>
                    <td>
                        <span class="badge-estado {{ $badgeClass }}">
                            {{ $cita->estadoCita->nombre ?? '—' }}
                        </span>
                    </td>
                    <td style="text-align: right;">
                        <a href="{{ route('citas.show', $cita) }}" class="btn-detail">Detalle &rarr;</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center; padding: 36px; color: #64748b;">
                        No se encontraron citas médicas programadas con los criterios seleccionados.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top: 18px;">
    {{ $citas->links() }}
</div>

@endsection
