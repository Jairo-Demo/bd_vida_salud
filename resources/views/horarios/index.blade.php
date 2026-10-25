@extends('layouts.app')

@section('title', 'Horarios de Atención y Fichas')

@push('styles')
<style>
    .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; }
    .page-title { font-size: 22px; font-weight: 700; color: #0f172a; }
    .page-subtitle { font-size: 13px; color: #64748b; margin-top: 2px; }
    .btn-create { background: linear-gradient(135deg, #0284c7 0%, #0d9488 100%); color: #ffffff; padding: 9px 18px; border-radius: 9px; text-decoration: none; font-size: 13.5px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25); }
    .filter-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px 20px; margin-bottom: 24px; }
    .filter-form { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
    .select-input { padding: 9px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; outline: none; background: #fff; min-width: 220px; }
    .btn-filter { background: #0f172a; color: #ffffff; padding: 9px 16px; border-radius: 8px; border: none; font-size: 13px; font-weight: 600; cursor: pointer; }
    .btn-clear { background: #f1f5f9; color: #475569; padding: 9px 14px; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 600; }
    .table-container { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; overflow-x: auto; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04); }
    .data-table { width: 100%; border-collapse: collapse; font-size: 13.5px; text-align: left; }
    .data-table th { background: #f8fafc; color: #475569; font-weight: 700; padding: 12px 16px; border-bottom: 1px solid #e2e8f0; font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.5px; }
    .data-table td { padding: 13px 16px; border-bottom: 1px solid #f1f5f9; color: #334155; }
    .badge-status { padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 700; }
    .badge-active { background-color: #dcfce7; color: #15803d; }
    .badge-inactive { background-color: #fee2e2; color: #b91c1c; }
    .action-group { display: flex; gap: 6px; justify-content: flex-end; }
    .btn-action { padding: 5px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; text-decoration: none; border: none; cursor: pointer; }
    .btn-show { background-color: #e0f2fe; color: #0369a1; }
    .btn-edit { background-color: #fef3c7; color: #92400e; }
    .btn-toggle { background-color: #fee2e2; color: #b91c1c; }
    .btn-toggle.active { background-color: #dcfce7; color: #15803d; }
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">Horarios de Atención y Fichas (P3)</h1>
        <p class="page-subtitle">Disponibilidad semanal de médicos y cálculo automático de turnos de 30 minutos (RF05 / CU13)</p>
    </div>
    @if(Auth::user()->rol->nombre === 'Administrador')
        <a href="{{ route('horarios.create') }}" class="btn-create">
            <span>+</span> Nueva Jornada de Atención
        </a>
    @endif
</div>

<!-- Filtros de Busqueda -->
<div class="filter-card">
    <form action="{{ route('horarios.index') }}" method="GET" class="filter-form">
        <select name="medico_id" class="select-input">
            <option value="">Todos los Médicos</option>
            @foreach($medicos as $med)
                <option value="{{ $med->id }}" {{ $medicoId == $med->id ? 'selected' : '' }}>
                    Dr(a). {{ $med->persona->nombre_completo ?? $med->codigo }}
                </option>
            @endforeach
        </select>

        <select name="dia_semana" class="select-input" style="min-width: 180px;">
            <option value="">Todos los Días</option>
            @foreach(['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo'] as $dia)
                <option value="{{ $dia }}" {{ $diaSemana === $dia ? 'selected' : '' }}>{{ $dia }}</option>
            @endforeach
        </select>

        <button type="submit" class="btn-filter">Filtrar</button>

        @if(!empty($medicoId) || !empty($diaSemana))
            <a href="{{ route('horarios.index') }}" class="btn-clear">Limpiar</a>
        @endif
    </form>
</div>

<!-- Tabla de Horarios -->
<div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th>Médico</th>
                <th>Día</th>
                <th>Horario</th>
                <th>Duración</th>
                <th>Fichas Calculadas</th>
                <th>Estado</th>
                <th style="text-align: right;">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($horarios as $h)
                <tr>
                    <td><strong>Dr(a). {{ $h->medico->persona->nombre_completo ?? '—' }}</strong></td>
                    <td><span style="font-weight: 700; color: #0f172a;">{{ $h->dia_semana }}</span></td>
                    <td>{{ substr($h->hora_inicio, 0, 5) }} - {{ substr($h->hora_fin, 0, 5) }}</td>
                    <td>{{ $h->duracion_cita_min }} min / turno</td>
                    <td>
                        <strong style="color: #0284c7;">{{ $h->fichas_count }} fichas</strong>
                    </td>
                    <td>
                        <span class="badge-status {{ $h->estado ? 'badge-active' : 'badge-inactive' }}">
                            {{ $h->estado ? 'Activo' : 'Inactivo' }}
                        </span>
                    </td>
                    <td>
                        <div class="action-group">
                            <a href="{{ route('horarios.show', $h) }}" class="btn-action btn-show" title="Ver bloques y fichas calculadas">Ver Fichas</a>

                            @if(Auth::user()->rol->nombre === 'Administrador')
                                <a href="{{ route('horarios.edit', $h) }}" class="btn-action btn-edit">Editar</a>

                                <form action="{{ route('horarios.toggleEstado', $h) }}" method="POST" style="display:inline;" onsubmit="return confirm('¿Desea cambiar el estado de este horario?');">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn-action btn-toggle {{ $h->estado ? '' : 'active' }}">
                                        {{ $h->estado ? 'Desactivar' : 'Activar' }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 32px; color: #64748b;">
                        No existen horarios de atención configurados con los filtros seleccionados.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top: 18px;">
    {{ $horarios->links() }}
</div>

@endsection
