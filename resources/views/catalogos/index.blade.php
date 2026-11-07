@extends('layouts.app')
@section('title', 'Catálogos Clínicos')
@push('styles') @include('partials.clinico-styles') @endpush
@section('content')
@php $esMed = $tab === 'medicamentos'; @endphp
<div class="page-header">
    <div>
        <h1 class="page-title">💊 Catálogos Clínicos (P7)</h1>
        <p class="page-subtitle">Medicamentos y alergias usados en recetas y en la verificación de alergias (CU27).</p>
    </div>
</div>

<div class="tabs">
    <a href="{{ route('catalogos.index', ['tab' => 'medicamentos']) }}" class="tab {{ $esMed ? 'on' : '' }}">Medicamentos</a>
    <a href="{{ route('catalogos.index', ['tab' => 'alergias']) }}" class="tab {{ !$esMed ? 'on' : '' }}">Alergias</a>
</div>

@if($errors->any())
    <div class="notice notice-err">@foreach($errors->all() as $e) <div>• {{ $e }}</div> @endforeach</div>
@endif

<div class="grid-2" style="grid-template-columns: minmax(300px, 1fr) minmax(420px, 2fr)">
    <!-- Formulario nuevo / editar -->
    <div class="card">
        <div class="card-title">{{ $editar ? 'Editar' : 'Nuevo' }} {{ $esMed ? 'medicamento' : 'alergia' }}</div>
        @if($esMed)
            <form action="{{ $editar ? route('catalogos.medicamentos.update', $editar) : route('catalogos.medicamentos.store') }}" method="POST">
                @csrf @if($editar) @method('PUT') @endif
                <div class="form-grid" style="grid-template-columns: 1fr">
                    <div class="form-group"><label class="form-label">Nombre comercial *</label><input name="nombre_comercial" class="form-control" required value="{{ old('nombre_comercial', $editar->nombre_comercial ?? '') }}"></div>
                    <div class="form-group"><label class="form-label">Principio activo *</label><input name="principio_activo" class="form-control" required value="{{ old('principio_activo', $editar->principio_activo ?? '') }}"></div>
                    <div class="form-group"><label class="form-label">Presentación *</label><input name="presentacion" class="form-control" required placeholder="Comprimido 500 mg" value="{{ old('presentacion', $editar->presentacion ?? '') }}"></div>
                </div>
                <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:14px">
                    @if($editar)<a href="{{ route('catalogos.index', ['tab' => 'medicamentos']) }}" class="btn btn-light">Cancelar</a>@endif
                    <button class="btn btn-primary">Guardar</button>
                </div>
            </form>
        @else
            <form action="{{ $editar ? route('catalogos.alergias.update', $editar) : route('catalogos.alergias.store') }}" method="POST">
                @csrf @if($editar) @method('PUT') @endif
                <div class="form-group"><label class="form-label">Nombre de la alergia *</label><input name="nombre" class="form-control" required value="{{ old('nombre', $editar->nombre ?? '') }}"></div>
                <p class="muted" style="margin-top:8px">Use el nombre del principio activo (por ejemplo, Ibuprofeno) para que la verificación de alergias al recetar lo detecte.</p>
                <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:14px">
                    @if($editar)<a href="{{ route('catalogos.index', ['tab' => 'alergias']) }}" class="btn btn-light">Cancelar</a>@endif
                    <button class="btn btn-primary">Guardar</button>
                </div>
            </form>
        @endif
    </div>

    <!-- Listado -->
    <div class="card" style="padding:0; overflow-x:auto">
        <form method="GET" action="{{ route('catalogos.index') }}" style="display:flex; gap:8px; padding:14px 16px">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <input name="buscar" class="form-control" placeholder="Buscar..." value="{{ $buscar }}">
            <button class="btn btn-light">Buscar</button>
        </form>
        <table class="data-table">
            <thead>
                @if($esMed)<tr><th>Nombre comercial</th><th>Principio activo</th><th>Presentación</th><th>Estado</th><th style="text-align:right">Acciones</th></tr>
                @else<tr><th>Alergia</th><th>Estado</th><th style="text-align:right">Acciones</th></tr>@endif
            </thead>
            <tbody>
            @forelse($items as $it)
                <tr>
                    @if($esMed)
                        <td><strong>{{ $it->nombre_comercial }}</strong></td><td>{{ $it->principio_activo }}</td><td>{{ $it->presentacion }}</td>
                    @else
                        <td><strong>{{ $it->nombre }}</strong></td>
                    @endif
                    <td><span class="badge {{ $it->estado ? 'b-on' : 'b-off' }}">{{ $it->estado ? 'Activo' : 'Inactivo' }}</span></td>
                    <td style="text-align:right; white-space:nowrap">
                        <a href="{{ route('catalogos.index', ['tab' => $tab, 'editar' => $it->id]) }}" class="act act-info">Editar</a>
                        <form action="{{ $esMed ? route('catalogos.medicamentos.estado', $it) : route('catalogos.alergias.estado', $it) }}" method="POST" style="display:inline">
                            @csrf @method('PATCH')
                            <button class="act {{ $it->estado ? 'act-del' : 'act-ok' }}">{{ $it->estado ? 'Desactivar' : 'Activar' }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" style="text-align:center; color:#64748b; padding:22px">Sin registros.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div style="padding: 10px 16px">{{ $items->links() }}</div>
    </div>
</div>
@endsection
