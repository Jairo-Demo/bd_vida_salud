@extends('layouts.app')

@section('title', 'Especialidades Médicas')

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
    }
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
        font-size: 13.5px;
        text-align: left;
    }
    .data-table th {
        background: #f8fafc;
        color: #475569;
        font-weight: 700;
        padding: 12px 16px;
        border-bottom: 1px solid #e2e8f0;
        font-size: 11.5px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .data-table td {
        padding: 13px 16px;
        border-bottom: 1px solid #f1f5f9;
        color: #334155;
    }
    .badge-status {
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 700;
    }
    .badge-active { background-color: #dcfce7; color: #15803d; }
    .badge-inactive { background-color: #fee2e2; color: #b91c1c; }
    .action-group {
        display: flex;
        gap: 6px;
        justify-content: flex-end;
    }
    .btn-action {
        padding: 5px 10px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        text-decoration: none;
        border: none;
        cursor: pointer;
    }
    .btn-show { background-color: #e0f2fe; color: #0369a1; }
    .btn-edit { background-color: #fef3c7; color: #92400e; }
    .btn-toggle { background-color: #fee2e2; color: #b91c1c; }
    .btn-toggle.active { background-color: #dcfce7; color: #15803d; }
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">Catálogo de Especialidades Médicas (P3)</h1>
        <p class="page-subtitle">Ramas de atención clínica habilitadas en la clínica (RF04 / CU10)</p>
    </div>
    @if(Auth::user()->rol->nombre === 'Administrador')
        <a href="{{ route('especialidades.create') }}" class="btn-create">
            <span>+</span> Nueva Especialidad
        </a>
    @endif
</div>

<div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th>Especialidad</th>
                <th>Nombre Normalizado</th>
                <th>Descripción</th>
                <th>Médicos Asignados</th>
                <th>Estado</th>
                <th style="text-align: right;">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($especialidades as $esp)
                <tr>
                    <td><strong>{{ $esp->nombre }}</strong></td>
                    <td><code>{{ $esp->nombre_normalizado }}</code></td>
                    <td>{{ $esp->descripcion ?: '—' }}</td>
                    <td><span style="font-weight: 700; color: #0284c7;">{{ $esp->medicos_count }} facultativos</span></td>
                    <td>
                        <span class="badge-status {{ $esp->estado ? 'badge-active' : 'badge-inactive' }}">
                            {{ $esp->estado ? 'Activa' : 'Inactiva' }}
                        </span>
                    </td>
                    <td>
                        <div class="action-group">
                            <a href="{{ route('especialidades.show', $esp) }}" class="btn-action btn-show">Detalle</a>
                            @if(Auth::user()->rol->nombre === 'Administrador')
                                <a href="{{ route('especialidades.edit', $esp) }}" class="btn-action btn-edit">Editar</a>
                                <form action="{{ route('especialidades.toggleEstado', $esp) }}" method="POST" style="display:inline;" onsubmit="return confirm('¿Desea cambiar el estado de esta especialidad?');">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn-action btn-toggle {{ $esp->estado ? '' : 'active' }}">
                                        {{ $esp->estado ? 'Desactivar' : 'Activar' }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 28px; color: #64748b;">
                        No existen especialidades registradas.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top: 18px;">
    {{ $especialidades->links() }}
</div>

@endsection
