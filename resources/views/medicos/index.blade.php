@extends('layouts.app')

@section('title', 'Plantel Médico')

@push('styles')
<style>
    .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; }
    .page-title { font-size: 22px; font-weight: 700; color: #0f172a; }
    .page-subtitle { font-size: 13px; color: #64748b; margin-top: 2px; }
    .btn-create { background: linear-gradient(135deg, #0284c7 0%, #0d9488 100%); color: #ffffff; padding: 9px 18px; border-radius: 9px; text-decoration: none; font-size: 13.5px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25); }
    .filter-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px 20px; margin-bottom: 24px; }
    .filter-form { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
    .search-input { flex: 1; min-width: 240px; padding: 9px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; outline: none; }
    .search-input:focus { border-color: #0284c7; }
    .select-input { padding: 9px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; outline: none; background: #fff; }
    .btn-filter { background: #0f172a; color: #ffffff; padding: 9px 16px; border-radius: 8px; border: none; font-size: 13px; font-weight: 600; cursor: pointer; }
    .btn-clear { background: #f1f5f9; color: #475569; padding: 9px 14px; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 600; }
    .table-container { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; overflow-x: auto; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04); }
    .data-table { width: 100%; border-collapse: collapse; font-size: 13.5px; text-align: left; }
    .data-table th { background: #f8fafc; color: #475569; font-weight: 700; padding: 12px 16px; border-bottom: 1px solid #e2e8f0; font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.5px; }
    .data-table td { padding: 13px 16px; border-bottom: 1px solid #f1f5f9; color: #334155; }
    .badge-status { padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 700; }
    .badge-active { background-color: #dcfce7; color: #15803d; }
    .badge-inactive { background-color: #fee2e2; color: #b91c1c; }
    .badge-esp { display: inline-block; background-color: #e0f2fe; color: #0369a1; padding: 2px 7px; border-radius: 5px; font-size: 11px; font-weight: 600; margin: 2px 2px 2px 0; }
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
        <h1 class="page-title">Gestión de Médicos y Facultativos (P3)</h1>
        <p class="page-subtitle">Registro de profesionales, especialidades asignadas y horarios (RF03 / CU11)</p>
    </div>
    @if(Auth::user()->rol->nombre === 'Administrador')
        <a href="{{ route('medicos.create') }}" class="btn-create">
            <span>+</span> Registrar Nuevo Médico
        </a>
    @endif
</div>

<!-- Filtros de Busqueda -->
<div class="filter-card">
    <form action="{{ route('medicos.index') }}" method="GET" class="filter-form">
        <input 
            type="text" 
            name="buscar" 
            value="{{ $buscar }}" 
            class="search-input" 
            placeholder="Buscar por Nombre, CI, Código (MED-XXX) o Registro Profesional..."
        >

        <select name="especialidad_id" class="select-input">
            <option value="">Todas las Especialidades</option>
            @foreach($todasEspecialidades as $esp)
                <option value="{{ $esp->id }}" {{ $especialidadId == $esp->id ? 'selected' : '' }}>
                    {{ $esp->nombre }}
                </option>
            @endforeach
        </select>

        <button type="submit" class="btn-filter">Buscar</button>

        @if(!empty($buscar) || !empty($especialidadId))
            <a href="{{ route('medicos.index') }}" class="btn-clear">Limpiar</a>
        @endif
    </form>
</div>

<!-- Tabla de Medicos -->
<div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th>Código</th>
                <th>Médico</th>
                <th>Registro Profesional</th>
                <th>Especialidades</th>
                <th>Teléfono</th>
                <th>Estado</th>
                <th style="text-align: right;">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($medicos as $medico)
                <tr>
                    <td><strong>{{ $medico->codigo }}</strong></td>
                    <td>
                        <strong>{{ $medico->persona->nombre_completo ?? '—' }}</strong>
                        <div style="font-size: 12px; color: #64748b;">CI: {{ $medico->persona->ci ?? '—' }}</div>
                    </td>
                    <td><code>{{ $medico->registro_profesional }}</code></td>
                    <td>
                        @forelse($medico->especialidades as $esp)
                            <span class="badge-esp">{{ $esp->nombre }}</span>
                        @empty
                            <span style="color: #94a3b8; font-size: 12px;">Sin especialidad</span>
                        @endforelse
                    </td>
                    <td>{{ $medico->persona->telefono ?? '—' }}</td>
                    <td>
                        <span class="badge-status {{ $medico->estado ? 'badge-active' : 'badge-inactive' }}">
                            {{ $medico->estado ? 'Activo' : 'Inactivo' }}
                        </span>
                    </td>
                    <td>
                        <div class="action-group">
                            <a href="{{ route('medicos.show', $medico) }}" class="btn-action btn-show" title="Ver Perfil y Horarios">Perfil</a>

                            @if(Auth::user()->rol->nombre === 'Administrador')
                                <a href="{{ route('medicos.edit', $medico) }}" class="btn-action btn-edit">Editar</a>

                                <form action="{{ route('medicos.toggleEstado', $medico) }}" method="POST" style="display:inline;" onsubmit="return confirm('¿Desea cambiar el estado de este médico?');">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn-action btn-toggle {{ $medico->estado ? '' : 'active' }}">
                                        {{ $medico->estado ? 'Desactivar' : 'Activar' }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 32px; color: #64748b;">
                        No se encontraron médicos con los filtros aplicados.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top: 18px;">
    {{ $medicos->links() }}
</div>

@endsection
