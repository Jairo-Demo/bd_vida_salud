@extends('layouts.app')
@section('title', 'Mi Perfil')
@push('styles') @include('partials.clinico-styles') @endpush
@section('content')
@php $p = $paciente; $per = $p->persona; @endphp
<div class="page-header">
    <div>
        <h1 class="page-title">👤 Mi Perfil</h1>
        <p class="page-subtitle">Actualice sus datos de contacto y su contraseña (CU09).</p>
    </div>
    @if(Route::has('historial.index'))<a href="{{ route('historial.index') }}" class="btn btn-light">📜 Mi historial de citas</a>@endif
</div>

@if($errors->any())
    <div class="notice notice-err">@foreach($errors->all() as $e) <div>• {{ $e }}</div> @endforeach</div>
@endif

<div class="grid-2">
    <div>
        <div class="card">
            <div class="card-title">Datos personales</div>
            <div class="row-detail"><span class="lbl">Nombre</span><span class="val">{{ $p->nombre_completo }}</span></div>
            <div class="row-detail"><span class="lbl">CI</span><span class="val">{{ $per->ci }}</span></div>
            <div class="row-detail"><span class="lbl">Código de paciente</span><span class="val">{{ $p->codigo }}</span></div>
            <div class="row-detail"><span class="lbl">Fecha de nacimiento</span><span class="val">{{ $p->fecha_nacimiento ? \Carbon\Carbon::parse($p->fecha_nacimiento)->format('d/m/Y') : '—' }}</span></div>
            <div class="row-detail"><span class="lbl">Usuario</span><span class="val">{{ $user->username }}</span></div>
            <p class="muted" style="margin-top:8px">El CI, el código y la fecha de nacimiento solo pueden ser corregidos por recepción.</p>
        </div>

        <form action="{{ route('perfil.password') }}" method="POST" class="card">
            @csrf @method('PUT')
            <div class="card-title">Cambiar contraseña</div>
            <div class="form-grid" style="grid-template-columns: 1fr">
                <div class="form-group"><label class="form-label">Contraseña actual *</label><input type="password" name="password_actual" class="form-control" required autocomplete="current-password"></div>
                <div class="form-group"><label class="form-label">Nueva contraseña * (mínimo 8 caracteres)</label><input type="password" name="password" class="form-control" required minlength="8" autocomplete="new-password"></div>
                <div class="form-group"><label class="form-label">Confirmar nueva contraseña *</label><input type="password" name="password_confirmation" class="form-control" required minlength="8" autocomplete="new-password"></div>
            </div>
            <div style="display:flex; justify-content:flex-end; margin-top:12px"><button class="btn btn-primary">Actualizar contraseña</button></div>
        </form>
    </div>

    <form action="{{ route('perfil.update') }}" method="POST" class="card">
        @csrf @method('PUT')
        <div class="card-title">Datos de contacto</div>
        <div class="form-grid" style="grid-template-columns: 1fr 1fr">
            <div class="form-group"><label class="form-label">Teléfono</label><input name="telefono" class="form-control" pattern="[0-9+\-\s]{6,20}" value="{{ old('telefono', $per->telefono) }}"></div>
            <div class="form-group"><label class="form-label">Correo *</label><input type="email" name="correo" class="form-control" required value="{{ old('correo', $per->correo) }}"></div>
            <div class="form-group full"><label class="form-label">Dirección</label><input name="direccion" class="form-control" value="{{ old('direccion', $p->direccion) }}"></div>
        </div>
        <div class="card-title" style="margin-top:18px">Contacto de emergencia</div>
        <div class="form-grid" style="grid-template-columns: 1fr 1fr">
            <div class="form-group full"><label class="form-label">Nombre</label><input name="contacto_emerg_nombre" class="form-control" value="{{ old('contacto_emerg_nombre', $p->contacto_emerg_nombre) }}"></div>
            <div class="form-group"><label class="form-label">Parentesco</label><input name="contacto_emerg_parentesco" class="form-control" value="{{ old('contacto_emerg_parentesco', $p->contacto_emerg_parentesco) }}"></div>
            <div class="form-group"><label class="form-label">Teléfono</label><input name="contacto_emerg_telefono" class="form-control" pattern="[0-9+\-\s]{6,20}" value="{{ old('contacto_emerg_telefono', $p->contacto_emerg_telefono) }}"></div>
        </div>
        <div style="display:flex; justify-content:flex-end; margin-top:16px"><button class="btn btn-primary">Guardar cambios</button></div>
    </form>
</div>
@endsection
