@extends('layouts.app')

@section('title', 'Registrar Nuevo Usuario (CU03 / CU04)')

@push('styles')
<style>
    .form-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 28px 32px;
        max-width: 820px;
        margin: 0 auto;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    }
    .form-title { font-size: 20px; font-weight: 700; color: #0f172a; margin-bottom: 4px; }
    .form-desc { font-size: 13px; color: #64748b; margin-bottom: 24px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; }
    
    .section-title {
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #0284c7;
        margin-top: 22px;
        margin-bottom: 14px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .grid-2 { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 18px; }
    .form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px; }
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
    .invalid-feedback { font-size: 11.5px; color: #dc2626; margin-top: 2px; }

    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        margin-top: 28px;
        padding-top: 18px;
        border-top: 1px solid #f1f5f9;
    }
    .btn-save {
        background: linear-gradient(135deg, #0284c7 0%, #0d9488 100%);
        color: #ffffff;
        padding: 10px 22px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        font-size: 13.5px;
    }
    .btn-cancel {
        background: #f1f5f9;
        color: #475569;
        padding: 10px 18px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        font-size: 13.5px;
    }
</style>
@endpush

@section('content')

<div class="form-card">
    <h1 class="form-title">Registrar Nuevo Usuario del Sistema</h1>
    <p class="form-desc">Crea los datos personales, la cuenta de acceso y asigna su rol oficial (CU03 / CU04).</p>

    <form action="{{ route('usuarios.store') }}" method="POST">
        @csrf

        <!-- 1. Datos Personales -->
        <div class="section-title">
            <span>👤</span> 1. Datos Personales del Titular
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label class="form-label" for="ci">Cédula de Identidad (CI) <span style="color: #ef4444;">*</span></label>
                <input type="text" name="ci" id="ci" class="form-control @error('ci') is-invalid @enderror" value="{{ old('ci') }}" required placeholder="Ej: 11325356">
                @error('ci') <span class="invalid-feedback">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="telefono">Teléfono / Celular</label>
                <input type="text" name="telefono" id="telefono" class="form-control @error('telefono') is-invalid @enderror" value="{{ old('telefono') }}" placeholder="Ej: 71234567">
                @error('telefono') <span class="invalid-feedback">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label class="form-label" for="nombres">Nombres <span style="color: #ef4444;">*</span></label>
                <input type="text" name="nombres" id="nombres" class="form-control @error('nombres') is-invalid @enderror" value="{{ old('nombres') }}" required placeholder="Ej: Carlos">
                @error('nombres') <span class="invalid-feedback">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="apellidos">Apellidos <span style="color: #ef4444;">*</span></label>
                <input type="text" name="apellidos" id="apellidos" class="form-control @error('apellidos') is-invalid @enderror" value="{{ old('apellidos') }}" required placeholder="Ej: Rodriguez">
                @error('apellidos') <span class="invalid-feedback">{{ $message }}</span> @enderror
            </div>
        </div>

        <!-- 2. Credenciales y Acceso -->
        <div class="section-title">
            <span>🛡️</span> 2. Credenciales de Acceso y Asignación de Rol (CU04)
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label class="form-label" for="username">Nombre de Usuario <span style="color: #ef4444;">*</span></label>
                <input type="text" name="username" id="username" class="form-control @error('username') is-invalid @enderror" value="{{ old('username') }}" required placeholder="Ej: crodriguez">
                @error('username') <span class="invalid-feedback">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="email">Correo Electrónico <span style="color: #ef4444;">*</span></label>
                <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required placeholder="ejemplo@vidasalud.com">
                @error('email') <span class="invalid-feedback">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="rol_id">Rol Asignado (CU04) <span style="color: #ef4444;">*</span></label>
            <select name="rol_id" id="rol_id" class="form-control @error('rol_id') is-invalid @enderror" required>
                <option value="">-- Seleccione el rol institucional --</option>
                @foreach($roles as $r)
                    <option value="{{ $r->id }}" {{ old('rol_id') == $r->id ? 'selected' : '' }}>
                        {{ $r->nombre }} &mdash; {{ $r->descripcion }}
                    </option>
                @endforeach
            </select>
            @error('rol_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label class="form-label" for="password">Contraseña Inicial <span style="color: #ef4444;">*</span></label>
                <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" required placeholder="Mínimo 6 caracteres">
                @error('password') <span class="invalid-feedback">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="password_confirmation">Confirmar Contraseña <span style="color: #ef4444;">*</span></label>
                <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required placeholder="Repita la contraseña">
            </div>
        </div>

        <!-- Botones de Acción -->
        <div class="form-actions">
            <a href="{{ route('usuarios.index') }}" class="btn-cancel">Cancelar</a>
            <button type="submit" class="btn-save">Guardar Usuario y Asignar Rol</button>
        </div>
    </form>
</div>

@endsection
