@extends('layouts.app')

@section('title', 'Gestión de Pacientes')

@push('styles')
<style>
    .page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 24px;
        flex-wrap: wrap;
        gap: 16px;
    }
    .page-title {
        font-size: 22px;
        font-weight: 700;
        color: #0f172a;
    }
    .page-subtitle {
        font-size: 13px;
        color: #64748b;
        margin-top: 2px;
    }
    .btn-create {
        background: linear-gradient(135deg, #0284c7 0%, #0d9488 100%);
        color: #ffffff;
        padding: 9px 18px;
        border-radius: 9px;
        text-decoration: none;
        font-size: 13.5px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25);
        transition: all 0.2s;
    }
    .btn-create:hover {
        opacity: 0.95;
        transform: translateY(-1px);
    }
    /* Barra de busqueda */
    .filter-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 18px 20px;
        margin-bottom: 24px;
    }
    .filter-form {
        display: flex;
        gap: 12px;
        align-items: center;
        flex-wrap: wrap;
    }
    .search-input {
        flex: 1;
        min-width: 260px;
        padding: 9px 14px;
        border: 1.5px solid #cbd5e1;
        border-radius: 8px;
        font-size: 13.5px;
        outline: none;
    }
    .search-input:focus {
        border-color: #0284c7;
    }
    .select-input {
        padding: 9px 14px;
        border: 1.5px solid #cbd5e1;
        border-radius: 8px;
        font-size: 13.5px;
        outline: none;
        background: #ffffff;
    }
    .btn-filter {
        background: #0f172a;
        color: #ffffff;
        padding: 9px 16px;
        border-radius: 8px;
        border: none;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
    }
    .btn-clear {
        background: #f1f5f9;
        color: #475569;
        padding: 9px 14px;
        border-radius: 8px;
        text-decoration: none;
        font-size: 13px;
        font-weight: 600;
    }
    /* Tabla */
    .table-container {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        overflow-x: auto;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    }
    .data-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
        font-size: 13.5px;
    }
    .data-table th {
        background: #f8fafc;
        color: #475569;
        font-weight: 700;
        padding: 12px 16px;
        border-bottom: 1px solid #e2e8f0;
        text-transform: uppercase;
        font-size: 11.5px;
        letter-spacing: 0.5px;
    }
    .data-table td {
        padding: 13px 16px;
        border-bottom: 1px solid #f1f5f9;
        color: #334155;
    }
    .data-table tr:hover {
        background-color: #f8fafc;
    }
    /* Insignias de estado */
    .badge-status {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 700;
    }
    .badge-active {
        background-color: #dcfce7;
        color: #15803d;
    }
    .badge-inactive {
        background-color: #fee2e2;
        color: #b91c1c;
    }
    /* Botones de accion */
    .action-group {
        display: flex;
        gap: 6px;
        align-items: center;
    }
    .btn-action {
        padding: 5px 10px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        text-decoration: none;
        border: none;
        cursor: pointer;
        transition: all 0.15s;
    }
    .btn-show { background-color: #e0f2fe; color: #0369a1; }
    .btn-show:hover { background-color: #bae6fd; }
    .btn-edit { background-color: #fef3c7; color: #92400e; }
    .btn-edit:hover { background-color: #fde68a; }
    .btn-deactivate { background-color: #fee2e2; color: #b91c1c; }
    .btn-deactivate:hover { background-color: #fecaca; }
    .btn-activate { background-color: #dcfce7; color: #15803d; }
    .btn-activate:hover { background-color: #bbf7d0; }
    .pagination-wrapper {
        margin-top: 18px;
    }
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">Módulo de Gestión de Pacientes (P2)</h1>
        <p class="page-subtitle">Registro clínico, búsqueda por CI o nombres y expediente demográfico (RF02 / CU06)</p>
    </div>
    @if(in_array(Auth::user()->rol->nombre ?? '', ['Administrador', 'Recepcionista']))
        <a href="{{ route('pacientes.create') }}" class="btn-create">
            <span>+</span> Registrar Nuevo Paciente
        </a>
    @endif
</div>

<!-- Filtros de Busqueda -->
<div class="filter-card">
    <form action="{{ route('pacientes.index') }}" method="GET" class="filter-form">
        <input 
            type="text" 
            name="buscar" 
            value="{{ $buscar }}" 
            class="search-input" 
            placeholder="Buscar por CI, Código (PAC-XXX), Nombres o Apellidos..."
        >
        
        <select name="estado" class="select-input">
            <option value="">Todos los Estados</option>
            <option value="1" {{ $estado === '1' ? 'selected' : '' }}>Solo Activos</option>
            <option value="0" {{ $estado === '0' ? 'selected' : '' }}>Solo Inactivos</option>
        </select>

        <button type="submit" class="btn-filter">Buscar</button>

        @if(!empty($buscar) || $estado !== null && $estado !== '')
            <a href="{{ route('pacientes.index') }}" class="btn-clear">Limpiar</a>
        @endif
    </form>
</div>

<!-- Tabla de Pacientes -->
<div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th>Código</th>
                <th>CI</th>
                <th>Nombre Completo</th>
                <th>Teléfono</th>
                <th>Sexo</th>
                <th>Tipo Sangre</th>
                <th>Estado</th>
                <th style="text-align: right;">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($pacientes as $paciente)
                <tr>
                    <td><strong>{{ $paciente->codigo }}</strong></td>
                    <td>{{ $paciente->persona->ci ?? 'S/CI' }}</td>
                    <td><strong>{{ $paciente->nombre_completo }}</strong></td>
                    <td>{{ $paciente->persona->telefono ?? '—' }}</td>
                    <td>{{ $paciente->sexo === 'M' ? 'Masculino' : 'Femenino' }}</td>
                    <td><span style="font-weight: 700; color: #0284c7;">{{ $paciente->tipo_sangre ?? '—' }}</span></td>
                    <td>
                        <span class="badge-status {{ $paciente->estado ? 'badge-active' : 'badge-inactive' }}">
                            {{ $paciente->estado ? 'Activo' : 'Inactivo' }}
                        </span>
                    </td>
                    <td>
                        <div class="action-group" style="justify-content: flex-end;">
                            {{-- Ver expediente completo e historial de citas --}}
                            <a href="{{ route('pacientes.show', $paciente) }}" class="btn-action btn-show" title="Ver Expediente e Historial">
                                Expediente
                            </a>

                            @if(in_array(Auth::user()->rol->nombre ?? '', ['Administrador', 'Recepcionista']))
                                {{-- Editar datos --}}
                                <a href="{{ route('pacientes.edit', $paciente) }}" class="btn-action btn-edit" title="Modificar datos">
                                    Editar
                                </a>

                                {{-- Desactivar o Activar logicamente (RN11) --}}
                                <form action="{{ route('pacientes.toggleEstado', $paciente) }}" method="POST" style="display: inline;" onsubmit="return confirm('¿Está seguro de cambiar el estado de este paciente?');">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn-action {{ $paciente->estado ? 'btn-deactivate' : 'btn-activate' }}">
                                        {{ $paciente->estado ? 'Desactivar' : 'Activar' }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center; padding: 32px; color: #64748b;">
                        No se encontraron pacientes que coincidan con la búsqueda.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Paginacion -->
<div class="pagination-wrapper">
    {{ $pacientes->links() }}
</div>

@endsection
