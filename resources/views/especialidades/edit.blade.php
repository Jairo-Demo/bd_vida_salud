@extends('layouts.app')

@section('title', 'Editar Especialidad')

@push('styles')
<style>
    .form-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 28px 32px; max-width: 650px; margin: 0 auto; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
    .form-title { font-size: 20px; font-weight: 700; color: #0f172a; margin-bottom: 4px; }
    .form-desc { font-size: 13px; color: #64748b; margin-bottom: 22px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; }
    .form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 18px; }
    .form-label { font-size: 13px; font-weight: 600; color: #334155; }
    .form-control { padding: 9px 13px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; outline: none; }
    .form-control:focus { border-color: #0284c7; }
    .invalid-feedback { font-size: 11.5px; color: #dc2626; margin-top: 2px; }
    .form-actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; padding-top: 18px; border-top: 1px solid #f1f5f9; }
    .btn-save { background: linear-gradient(135deg, #0284c7 0%, #0d9488 100%); color: #fff; padding: 10px 22px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; }
    .btn-cancel { background: #f1f5f9; color: #475569; padding: 10px 18px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 13.5px; }
</style>
@endpush

@section('content')

<div class="form-card">
    <h1 class="form-title">Modificar Especialidad Médica</h1>
    <p class="form-desc">Actualizando información de: <strong>{{ $especialidad->nombre }}</strong></p>

    <form action="{{ route('especialidades.update', $especialidad) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label class="form-label" for="nombre">Nombre de la Especialidad <span style="color: #ef4444;">*</span></label>
            <input type="text" name="nombre" id="nombre" class="form-control @error('nombre_normalizado') is-invalid @enderror" value="{{ old('nombre', $especialidad->nombre) }}" required>
            @error('nombre_normalizado') <span class="invalid-feedback">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="descripcion">Descripción</label>
            <textarea name="descripcion" id="descripcion" rows="3" class="form-control">{{ old('descripcion', $especialidad->descripcion) }}</textarea>
        </div>

        <div class="form-actions">
            <a href="{{ route('especialidades.index') }}" class="btn-cancel">Cancelar</a>
            <button type="submit" class="btn-save">Actualizar Especialidad</button>
        </div>
    </form>
</div>

@endsection
