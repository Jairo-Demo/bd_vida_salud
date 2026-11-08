@extends('layouts.app')
@section('title', 'Roles y Permisos')
@push('styles')
@include('partials.clinico-styles')
<style>
    .matrix th.rol, .matrix td.chk { text-align: center; width: 120px; }
    .matrix td.chk input { width: 17px; height: 17px; accent-color: #0284c7; cursor: pointer; }
    .matrix tr.grupo td { background: #f0f9ff; color: #0369a1; font-weight: 700; font-size: 11.5px; text-transform: uppercase; letter-spacing: .4px; }
    .code { font-family: monospace; font-size: 11.5px; color: #64748b; }
</style>
@endpush
@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">🛡️ Roles y Permisos (P1)</h1>
        <p class="page-subtitle">Asigne los permisos de cada rol (CU04). El rol de cada usuario se asigna en Gestión de Usuarios (CU03).</p>
    </div>
</div>
<div class="notice notice-info">El Administrador conserva todos los permisos para no perder el acceso al sistema. Los cambios se aplican en el siguiente acceso de cada usuario.</div>

<form action="{{ route('roles.update') }}" method="POST" class="card" style="padding:0; overflow-x:auto" onsubmit="return confirm('¿Guardar la matriz de permisos? Los usuarios de cada rol solo podrán usar las opciones marcadas.')">
    @csrf @method('PUT')
    <table class="data-table matrix">
        <thead>
            <tr>
                <th>Permiso</th>
                @foreach($roles as $r)<th class="rol">{{ $r->nombre }}<br><span class="muted" style="font-weight:500">{{ count($asignados[$r->id]) }} permisos</span></th>@endforeach
            </tr>
        </thead>
        <tbody>
        @foreach($grupos as $grupo => $permisos)
            @continue($permisos->isEmpty())
            <tr class="grupo"><td colspan="{{ $roles->count() + 1 }}">{{ $grupo }}</td></tr>
            @foreach($permisos as $p)
                <tr>
                    <td>{{ $p->descripcion }}<br><span class="code">{{ $p->codigo }}</span></td>
                    @foreach($roles as $r)
                        <td class="chk">
                            <input type="checkbox" name="permisos[{{ $r->id }}][]" value="{{ $p->id }}"
                                   {{ in_array($p->id, $asignados[$r->id]) ? 'checked' : '' }}
                                   {{ $r->nombre === 'Administrador' ? 'disabled' : '' }}>
                        </td>
                    @endforeach
                </tr>
            @endforeach
        @endforeach
        </tbody>
    </table>
    <div style="display:flex; justify-content:flex-end; padding: 14px 18px">
        <button class="btn btn-primary">Guardar permisos</button>
    </div>
</form>
@endsection
