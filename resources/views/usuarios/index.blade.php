@extends('layouts.app')

@section('title', 'Gestión de Usuarios y Roles (CU03 / CU04)')

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
        flex-wrap: wrap;
        gap: 16px;
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
    .btn-create {
        background: linear-gradient(135deg, #0284c7 0%, #0d9488 100%);
        color: #ffffff;
        padding: 10px 20px;
        border-radius: 9px;
        font-weight: 600;
        font-size: 13.5px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: opacity 0.2s;
    }
    .btn-create:hover { opacity: 0.92; }

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
        grid-template-columns: 2fr 1.5fr 1fr auto;
        gap: 14px;
        align-items: flex-end;
    }
    .form-group { display: flex; flex-direction: column; gap: 6px; }
    .form-label { font-size: 12.5px; font-weight: 600; color: #334155; }
    .form-control {
        padding: 9px 13px;
        border: 1.5px solid #cbd5e1;
        border-radius: 8px;
        font-size: 13.5px;
        outline: none;
        background: #ffffff;
    }
    .form-control:focus { border-color: #0284c7; }
    .btn-filter {
        background-color: #0284c7;
        color: #ffffff;
        border: none;
        padding: 10px 18px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        font-size: 13px;
    }
    .btn-reset {
        background-color: #f1f5f9;
        color: #475569;
        padding: 10px 14px;
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
        font-size: 13.5px;
    }
    .data-table th {
        background-color: #f8fafc;
        color: #475569;
        font-weight: 600;
        padding: 14px 18px;
        border-bottom: 1px solid #e2e8f0;
        text-transform: uppercase;
        font-size: 11.5px;
        letter-spacing: 0.5px;
    }
    .data-table td {
        padding: 14px 18px;
        border-bottom: 1px solid #f1f5f9;
        color: #1e293b;
    }
    .data-table tr:hover td { background-color: #f8fafc; }

    /* Badges */
    .badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 11.5px;
        font-weight: 700;
    }
    .badge-admin { background-color: #ede9fe; color: #6d28d9; }
    .badge-recep { background-color: #e0f2fe; color: #0284c7; }
    .badge-med { background-color: #dcfce7; color: #15803d; }
    .badge-pac { background-color: #fef3c7; color: #b45309; }

    .badge-active { background-color: #dcfce7; color: #15803d; }
    .badge-inactive { background-color: #fee2e2; color: #b91c1c; }

    .actions-cell { display: flex; gap: 8px; align-items: center; }
    .btn-sm {
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        text-decoration: none;
        border: 1px solid transparent;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .btn-edit { background: #f0fdf4; color: #15803d; border-color: #bbf7d0; }
    .btn-toggle { background: #fef2f2; color: #b91c1c; border-color: #fecaca; }
    .btn-enable { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }
</style>
@endpush

@section('content')

<!-- Cabecera del Módulo -->
<div class="module-header">
    <div class="module-info">
        <div class="module-icon">👥</div>
        <div>
            <h1 class="module-title">Gestión de Usuarios y Roles (CU03 / CU04)</h1>
            <p class="module-subtitle">Administración de cuentas del personal, asignación de roles y control de acceso (Ciclo 1).</p>
        </div>
    </div>
    <a href="{{ route('usuarios.create') }}" class="btn-create">
        <span>+</span> Registrar Nuevo Usuario
    </a>
</div>

<!-- Filtros de Búsqueda -->
<div class="filter-card">
    <form action="{{ route('usuarios.index') }}" method="GET" class="filter-grid">
        <div class="form-group">
            <label class="form-label" for="buscar">Búsqueda rápida</label>
            <input type="text" name="buscar" id="buscar" class="form-control" placeholder="Buscar por CI, nombre, usuario o email..." value="{{ $buscar }}">
        </div>

        <div class="form-group">
            <label class="form-label" for="rol_id">Rol asignado</label>
            <select name="rol_id" id="rol_id" class="form-control">
                <option value="">-- Todos los roles --</option>
                @foreach($roles as $r)
                    <option value="{{ $r->id }}" {{ $rolId == $r->id ? 'selected' : '' }}>{{ $r->nombre }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label class="form-label" for="estado">Estado</label>
            <select name="estado" id="estado" class="form-control">
                <option value="">-- Todos --</option>
                <option value="1" {{ $estado === '1' ? 'selected' : '' }}>Activos</option>
                <option value="0" {{ $estado === '0' ? 'selected' : '' }}>Inactivos</option>
            </select>
        </div>

        <div style="display: flex; gap: 8px;">
            <button type="submit" class="btn-filter">Filtrar</button>
            @if($buscar || $rolId || $estado !== null && $estado !== '')
                <a href="{{ route('usuarios.index') }}" class="btn-reset">Limpiar</a>
            @endif
        </div>
    </form>
</div>

<!-- Tabla de Usuarios -->
<div class="table-card">
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Usuario</th>
                <th>Nombre Completo / CI</th>
                <th>Correo Electrónico</th>
                <th>Rol Asignado (CU04)</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($usuarios as $u)
                @php
                    $badgeRol = match($u->rol->nombre ?? '') {
                        'Administrador' => 'badge-admin',
                        'Recepcionista' => 'badge-recep',
                        'Medico' => 'badge-med',
                        'Paciente' => 'badge-pac',
                        default => 'badge-recep'
                    };
                @endphp
                <tr>
                    <td style="font-weight: 700; color: #64748b;">#{{ $u->id }}</td>
                    <td>
                        <strong style="color: #0284c7;">{{ $u->username }}</strong>
                    </td>
                    <td>
                        <div><strong>{{ $u->persona->nombre_completo ?? $u->name }}</strong></div>
                        <div style="font-size: 12px; color: #64748b;">CI: {{ $u->persona->ci ?? '—' }}</div>
                    </td>
                    <td>{{ $u->email }}</td>
                    <td>
                        <span class="badge {{ $badgeRol }}">{{ $u->rol->nombre ?? 'Sin Rol' }}</span>
                    </td>
                    <td>
                        <span class="badge {{ $u->estado ? 'badge-active' : 'badge-inactive' }}">
                            {{ $u->estado ? 'Activo' : 'Inactivo' }}
                        </span>
                    </td>
                    <td>
                        <div class="actions-cell">
                            <a href="{{ route('usuarios.edit', $u) }}" class="btn-sm btn-edit" title="Editar cuenta y rol">
                                ✏️ Editar
                            </a>
                            @if($u->id !== Auth::id())
                                <form action="{{ route('usuarios.toggleEstado', $u) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn-sm {{ $u->estado ? 'btn-toggle' : 'btn-enable' }}" onclick="return confirm('¿Desea cambiar el estado de acceso de este usuario?');">
                                        {{ $u->estado ? 'Desactivar' : 'Activar' }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 32px; color: #64748b;">
                        No se encontraron usuarios que coincidan con los criterios.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top: 20px;">
    {{ $usuarios->links() }}
</div>

@endsection
