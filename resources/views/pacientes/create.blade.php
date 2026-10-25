@extends('layouts.app')

@section('title', 'Registrar Nuevo Paciente')

@push('styles')
<style>
    .form-container {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 28px 32px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        max-width: 900px;
        margin: 0 auto;
    }
    .form-title {
        font-size: 20px;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 4px;
    }
    .form-desc {
        font-size: 13px;
        color: #64748b;
        margin-bottom: 24px;
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 14px;
    }
    .form-section-title {
        font-size: 14px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #0284c7;
        margin: 20px 0 12px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 16px 20px;
    }
    .form-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .form-group.full-width {
        grid-column: 1 / -1;
    }
    .form-label {
        font-size: 12.5px;
        font-weight: 600;
        color: #334155;
    }
    .form-label span.req {
        color: #ef4444;
    }
    .form-control {
        padding: 9px 13px;
        border: 1.5px solid #cbd5e1;
        border-radius: 8px;
        font-size: 13.5px;
        outline: none;
        transition: border-color 0.2s;
    }
    .form-control:focus {
        border-color: #0284c7;
    }
    .form-control.is-invalid {
        border-color: #ef4444;
        background-color: #fef2f2;
    }
    .invalid-feedback {
        font-size: 11.5px;
        color: #dc2626;
        margin-top: 2px;
    }
    .form-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 12px;
        margin-top: 28px;
        padding-top: 20px;
        border-top: 1px solid #f1f5f9;
    }
    .btn-save {
        background: linear-gradient(135deg, #0284c7 0%, #0d9488 100%);
        color: #ffffff;
        padding: 10px 22px;
        border: none;
        border-radius: 8px;
        font-size: 13.5px;
        font-weight: 600;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25);
    }
    .btn-cancel {
        background: #f1f5f9;
        color: #475569;
        padding: 10px 20px;
        border-radius: 8px;
        text-decoration: none;
        font-size: 13.5px;
        font-weight: 600;
    }
</style>
@endpush

@section('content')

<div class="form-container">
    <h1 class="form-title">Registrar Nuevo Paciente</h1>
    <p class="form-desc">Complete la información para abrir un nuevo expediente clínico en la Clínica Vida Salud (RF02).</p>

    <form action="{{ route('pacientes.store') }}" method="POST">
        @csrf

        <!-- Seccion 1: Datos Personales -->
        <div class="form-section-title">
            <span>1. Datos Personales y Demográficos</span>
        </div>
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Código del Paciente</label>
                <input type="text" class="form-control" value="{{ $codigoSugerido }}" readonly style="background-color: #f8fafc; font-weight: 700;">
            </div>

            <div class="form-group">
                <label class="form-label" for="ci">Documento de Identidad (CI) <span class="req">*</span></label>
                <input type="text" name="ci" id="ci" class="form-control @error('ci') is-invalid @enderror" value="{{ old('ci') }}" placeholder="Ej. 11325356" required>
                @error('ci') <span class="invalid-feedback">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="nombres">Nombres <span class="req">*</span></label>
                <input type="text" name="nombres" id="nombres" class="form-control @error('nombres') is-invalid @enderror" value="{{ old('nombres') }}" placeholder="Nombres del paciente" required>
                @error('nombres') <span class="invalid-feedback">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="apellidos">Apellidos <span class="req">*</span></label>
                <input type="text" name="apellidos" id="apellidos" class="form-control @error('apellidos') is-invalid @enderror" value="{{ old('apellidos') }}" placeholder="Apellidos del paciente" required>
                @error('apellidos') <span class="invalid-feedback">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="fecha_nacimiento">Fecha de Nacimiento <span class="req">*</span></label>
                <input type="date" name="fecha_nacimiento" id="fecha_nacimiento" class="form-control @error('fecha_nacimiento') is-invalid @enderror" value="{{ old('fecha_nacimiento') }}" required>
                @error('fecha_nacimiento') <span class="invalid-feedback">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="sexo">Sexo <span class="req">*</span></label>
                <select name="sexo" id="sexo" class="form-control @error('sexo') is-invalid @enderror" required>
                    <option value="">Seleccione...</option>
                    <option value="M" {{ old('sexo') === 'M' ? 'selected' : '' }}>Masculino</option>
                    <option value="F" {{ old('sexo') === 'F' ? 'selected' : '' }}>Femenino</option>
                </select>
                @error('sexo') <span class="invalid-feedback">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="telefono">Teléfono / Celular</label>
                <input type="text" name="telefono" id="telefono" class="form-control @error('telefono') is-invalid @enderror" value="{{ old('telefono') }}" placeholder="Ej. 70012345">
                @error('telefono') <span class="invalid-feedback">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="correo">Correo Electrónico</label>
                <input type="email" name="correo" id="correo" class="form-control @error('correo') is-invalid @enderror" value="{{ old('correo') }}" placeholder="correo@ejemplo.com">
                @error('correo') <span class="invalid-feedback">{{ $message }}</span> @enderror
            </div>

            <div class="form-group full-width">
                <label class="form-label" for="direccion">Dirección de Domicilio</label>
                <input type="text" name="direccion" id="direccion" class="form-control @error('direccion') is-invalid @enderror" value="{{ old('direccion') }}" placeholder="Av., Calle, Nro de casa, Barrio">
                @error('direccion') <span class="invalid-feedback">{{ $message }}</span> @enderror
            </div>
        </div>

        <!-- Seccion 2: Informacion Clinica Base -->
        <div class="form-section-title">
            <span>2. Información Médica y Alergias</span>
        </div>
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="tipo_sangre">Tipo de Sangre</label>
                <select name="tipo_sangre" id="tipo_sangre" class="form-control @error('tipo_sangre') is-invalid @enderror">
                    <option value="">Desconocido / Sin registrar</option>
                    @foreach(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $ts)
                        <option value="{{ $ts }}" {{ old('tipo_sangre') === $ts ? 'selected' : '' }}>{{ $ts }}</option>
                    @endforeach
                </select>
                @error('tipo_sangre') <span class="invalid-feedback">{{ $message }}</span> @enderror
            </div>

            <div class="form-group full-width">
                <label class="form-label" for="otras_alergias">Alergias Conocidas y Observaciones</label>
                <textarea name="otras_alergias" id="otras_alergias" rows="2" class="form-control @error('otras_alergias') is-invalid @enderror" placeholder="Ej. Alergia a la penicilina, ibuprofeno, etc.">{{ old('otras_alergias') }}</textarea>
                @error('otras_alergias') <span class="invalid-feedback">{{ $message }}</span> @enderror
            </div>
        </div>

        <!-- Seccion 3: Contacto de Emergencia -->
        <div class="form-section-title">
            <span>3. Contacto de Emergencia</span>
        </div>
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="contacto_emerg_nombre">Nombre del Contacto</label>
                <input type="text" name="contacto_emerg_nombre" id="contacto_emerg_nombre" class="form-control" value="{{ old('contacto_emerg_nombre') }}" placeholder="Nombre completo">
            </div>

            <div class="form-group">
                <label class="form-label" for="contacto_emerg_parentesco">Parentesco</label>
                <input type="text" name="contacto_emerg_parentesco" id="contacto_emerg_parentesco" class="form-control" value="{{ old('contacto_emerg_parentesco') }}" placeholder="Padre, Madre, Esposo(a)...">
            </div>

            <div class="form-group">
                <label class="form-label" for="contacto_emerg_telefono">Teléfono de Emergencia</label>
                <input type="text" name="contacto_emerg_telefono" id="contacto_emerg_telefono" class="form-control" value="{{ old('contacto_emerg_telefono') }}" placeholder="Número de contacto">
            </div>
        </div>

        <!-- Botones de accion -->
        <div class="form-actions">
            <a href="{{ route('pacientes.index') }}" class="btn-cancel">Cancelar</a>
            <button type="submit" class="btn-save">Guardar Paciente</button>
        </div>
    </form>
</div>

@endsection
