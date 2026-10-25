@extends('layouts.app')

@section('title', 'Bitácora de Auditoría del Sistema (RN15 / CU05)')

@push('styles')
<style>
    .module-header {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 24px 28px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    }
    .module-info { display: flex; align-items: center; gap: 16px; }
    .module-icon {
        width: 52px;
        height: 52px;
        background: linear-gradient(135deg, #0284c7 0%, #0d9488 100%);
        color: #ffffff;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
    }
    .module-title { font-size: 20px; font-weight: 700; color: #0f172a; margin-bottom: 2px; }
    .module-subtitle { font-size: 13px; color: #64748b; }

    /* Filtros */
    .filter-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 20px 24px;
        margin-bottom: 24px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    }
    .filter-grid {
        display: grid;
        grid-template-columns: 2fr 1.5fr 1.5fr 1fr 1fr auto;
        gap: 12px;
        align-items: flex-end;
    }
    .form-group { display: flex; flex-direction: column; gap: 6px; }
    .form-label { font-size: 12px; font-weight: 600; color: #334155; }
    .form-control {
        padding: 8px 12px;
        border: 1.5px solid #cbd5e1;
        border-radius: 8px;
        font-size: 13px;
        outline: none;
        background: #ffffff;
    }
    .form-control:focus { border-color: #0284c7; }
    .btn-filter {
        background-color: #0284c7;
        color: #ffffff;
        border: none;
        padding: 9px 16px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        font-size: 13px;
    }
    .btn-reset {
        background-color: #f1f5f9;
        color: #475569;
        padding: 9px 12px;
        border-radius: 8px;
        text-decoration: none;
        font-size: 13px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
    }

    /* Tabla */
    .table-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    }
    .data-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
        font-size: 13px;
    }
    .data-table th {
        background-color: #f8fafc;
        color: #475569;
        font-weight: 600;
        padding: 12px 16px;
        border-bottom: 1px solid #e2e8f0;
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 0.5px;
    }
    .data-table td {
        padding: 12px 16px;
        border-bottom: 1px solid #f1f5f9;
        color: #1e293b;
    }
    .data-table tr:hover td { background-color: #f8fafc; }

    .badge-accion {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 700;
        font-family: monospace;
    }
    .accion-crear { background-color: #dcfce7; color: #15803d; }
    .accion-actualizar { background-color: #e0f2fe; color: #0284c7; }
    .accion-desactivar { background-color: #fee2e2; color: #b91c1c; }
    .accion-login { background-color: #ede9fe; color: #6d28d9; }
</style>
@endpush

@section('content')

<!-- Cabecera del Módulo -->
<div class="module-header">
    <div class="module-info">
        <div class="module-icon">🛡️</div>
        <div>
            <h1 class="module-title">Bitácora de Auditoría del Sistema (RN15 / CU05)</h1>
            <p class="module-subtitle">Registro cronológico e inmutable de operaciones, autenticaciones, altas y modificaciones.</p>
        </div>
    </div>
</div>

<!-- Filtros de Búsqueda -->
<div class="filter-card">
    <form action="{{ route('bitacora.index') }}" method="GET" class="filter-grid">
        <div class="form-group">
            <label class="form-label" for="buscar">Búsqueda rápida</label>
            <input type="text" name="buscar" id="buscar" class="form-control" placeholder="Detalles, IP, tabla..." value="{{ $buscar }}">
        </div>

        <div class="form-group">
            <label class="form-label" for="usuario_id">Usuario</label>
            <select name="usuario_id" id="usuario_id" class="form-control">
                <option value="">-- Todos --</option>
                @foreach($usuarios as $u)
                    <option value="{{ $u->id }}" {{ $usuarioId == $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ $u->username }})</option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label class="form-label" for="accion">Acción</label>
            <select name="accion" id="accion" class="form-control">
                <option value="">-- Todas --</option>
                @foreach($acciones as $a)
                    <option value="{{ $a }}" {{ $accion == $a ? 'selected' : '' }}>{{ $a }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label class="form-label" for="fecha_desde">Desde</label>
            <input type="date" name="fecha_desde" id="fecha_desde" class="form-control" value="{{ $fechaDesde }}">
        </div>

        <div class="form-group">
            <label class="form-label" for="fecha_hasta">Hasta</label>
            <input type="date" name="fecha_hasta" id="fecha_hasta" class="form-control" value="{{ $fechaHasta }}">
        </div>

        <div style="display: flex; gap: 8px;">
            <button type="submit" class="btn-filter">Filtrar</button>
            @if($buscar || $usuarioId || $accion || $fechaDesde || $fechaHasta)
                <a href="{{ route('bitacora.index') }}" class="btn-reset">Limpiar</a>
            @endif
        </div>
    </form>
</div>

<!-- Tabla de Registros de Bitácora -->
<div class="table-card">
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Fecha y Hora</th>
                <th>Usuario</th>
                <th>Acción</th>
                <th>Tabla / Reg. ID</th>
                <th>Detalles de la Operación</th>
                <th>IP / Canal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($bitacoras as $b)
                @php
                    $claseAccion = 'accion-crear';
                    if (str_contains($b->accion, 'ACTUALIZAR') || str_contains($b->accion, 'CAMBIAR')) {
                        $claseAccion = 'accion-actualizar';
                    } elseif (str_contains($b->accion, 'DESACTIVAR') || str_contains($b->accion, 'CANCELAR')) {
                        $claseAccion = 'accion-desactivar';
                    } elseif (str_contains($b->accion, 'LOGIN') || str_contains($b->accion, 'LOGOUT')) {
                        $claseAccion = 'accion-login';
                    }
                @endphp
                <tr>
                    <td style="font-weight: 700; color: #64748b;">#{{ $b->id }}</td>
                    <td style="white-space: nowrap; font-weight: 600; color: #334155;">
                        {{ \Carbon\Carbon::parse($b->fecha_hora)->format('d/m/Y H:i:s') }}
                    </td>
                    <td>
                        <strong>{{ $b->usuario->name ?? 'Sistema' }}</strong>
                        <div style="font-size: 11.5px; color: #64748b;">{{ $b->usuario->rol->nombre ?? 'N/A' }}</div>
                    </td>
                    <td>
                        <span class="badge-accion {{ $claseAccion }}">{{ $b->accion }}</span>
                    </td>
                    <td style="font-family: monospace; font-size: 12px; color: #475569;">
                        {{ $b->tabla_afectada }} #{{ $b->registro_id ?? '-' }}
                    </td>
                    <td style="max-width: 380px; word-break: break-word; font-size: 12.5px;">
                        {{ $b->detalles }}
                    </td>
                    <td style="white-space: nowrap; font-size: 12px; color: #64748b;">
                        <div>{{ $b->ip ?? '127.0.0.1' }}</div>
                        <span style="font-size: 10.5px; background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-weight: 600;">{{ $b->canal }}</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 32px; color: #64748b;">
                        No hay registros en la bitácora que coincidan con la búsqueda.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top: 20px;">
    {{ $bitacoras->links() }}
</div>

@endsection
