@extends('layouts.app')

@section('title', 'Registrar Médico')

@push('styles')
<style>
    .form-container { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 28px 32px; max-width: 860px; margin: 0 auto; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
    .form-title { font-size: 20px; font-weight: 700; color: #0f172a; margin-bottom: 4px; }
    .form-desc { font-size: 13px; color: #64748b; margin-bottom: 22px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; }
    .form-section-title { font-size: 13.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #0284c7; margin: 20px 0 12px; }
    .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px 20px; }
    .form-group { display: flex; flex-direction: column; gap: 6px; }
    .form-label { font-size: 12.5px; font-weight: 600; color: #334155; }
    .form-control { padding: 9px 13px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; outline: none; }
    .form-control:focus { border-color: #0284c7; }
    .invalid-feedback { font-size: 11.5px; color: #dc2626; margin-top: 2px; }
    .checkbox-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; margin-top: 8px; }
    .checkbox-card { border: 1.5px solid #e2e8f0; border-radius: 8px; padding: 10px 14px; display: flex; align-items: center; gap: 10px; cursor: pointer; transition: all 0.15s; }
    .checkbox-card:hover { border-color: #0284c7; background: #f0fdf4; }
    .checkbox-card input { width: 16px; height: 16px; accent-color: #0284c7; }
    .form-actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 28px; padding-top: 18px; border-top: 1px solid #f1f5f9; }
    .btn-save { background: linear-gradient(135deg, #0284c7 0%, #0d9488 100%); color: #fff; padding: 10px 22px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; }
    .btn-cancel { background: #f1f5f9; color: #475569; padding: 10px 18px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 13.5px; }
</style>
@endpush

@section('content')

<div class="form-container">
    <h1 class="form-title">Registrar Nuevo Médico</h1>
    <p class="form-desc">Alta de facultativo en el plantel clínico y asignación de especialidades (RF03 / CU11 - CU12).</p>

    <form action="{{ route('medicos.store') }}" method="POST">
        @csrf

        <!-- 1. Datos Personales -->
        <div class="form-section-title">1. Datos Personales</div>
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Código del Médico</label>
                <input type="text" class="form-control" value="{{ $codigoSugerido }}" readonly style="background-color: #f8fafc; font-weight: 700;">
            </div>

            <div class="form-group">
                <label class="form-label" for="ci">Documento de Identidad (CI) <span style="color: #ef4444;">*</span></label>
                <input type="text" name="ci" id="ci" class="form-control @error('ci') is-invalid @enderror" value="{{ old('ci') }}" placeholder="Ej. 3000003" required>
                @error('ci') <span class="invalid-feedback">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="nombres">Nombres <span style="color: #ef4444;">*</span></label>
                <input type="text" name="nombres" id="nombres" class="form-control @error('nombres') is-invalid @enderror" value="{{ old('nombres') }}" placeholder="Ej. Alejandro" required>
                @error('nombres') <span class="invalid-feedback">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="apellidos">Apellidos <span style="color: #ef4444;">*</span></label>
                <input type="text" name="apellidos" id="apellidos" class="form-control @error('apellidos') is-invalid @enderror" value="{{ old('apellidos') }}" placeholder="Ej. Roca Melgar" required>
                @error('apellidos') <span class="invalid-feedback">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="registro_profesional">Registro Profesional (Colegio Médico) <span style="color: #ef4444;">*</span></label>
                <input type="text" name="registro_profesional" id="registro_profesional" class="form-control @error('registro_profesional') is-invalid @enderror" value="{{ old('registro_profesional') }}" placeholder="Ej. COLMED-8492" required>
                @error('registro_profesional') <span class="invalid-feedback">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="telefono">Teléfono / Celular</label>
                <input type="text" name="telefono" id="telefono" class="form-control" value="{{ old('telefono') }}" placeholder="Ej. 72233445">
            </div>

            <div class="form-group" style="grid-column: 1 / -1;">
                <label class="form-label" for="correo">Correo Electrónico Institucional</label>
                <input type="email" name="correo" id="correo" class="form-control" value="{{ old('correo') }}" placeholder="dr.apellido@vidasalud.com">
            </div>
        </div>

        <!-- 2. Asignacion de Especialidades N:M -->
        <div class="form-section-title">2. Asignación de Especialidades Médicas (CU12)</div>
        <p style="font-size: 12.5px; color: #64748b; margin-bottom: 8px;">Marque una o varias ramas en las que el médico brindará atención:</p>
        
        @error('especialidades') 
            <div style="font-size: 12px; color: #dc2626; margin-bottom: 8px; font-weight: 600;">{{ $message }}</div> 
        @enderror

        <div class="checkbox-grid">
            @foreach($especialidades as $esp)
                <label class="checkbox-card">
                    <input type="checkbox" name="especialidades[]" value="{{ $esp->id }}" {{ in_array($esp->id, old('especialidades', [])) ? 'checked' : '' }}>
                    <span style="font-size: 13px; font-weight: 600; color: #334155;">{{ $esp->nombre }}</span>
                </label>
            @endforeach
        </div>

        <!-- Botones de accion -->
        <div class="form-actions">
            <a href="{{ route('medicos.index') }}" class="btn-cancel">Cancelar</a>
            <button type="submit" class="btn-save">Guardar Médico</button>
        </div>
    </form>
</div>

@endsection
