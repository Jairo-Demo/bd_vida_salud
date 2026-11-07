@extends('layouts.app')
@section('title', 'Atender Cita ' . $cita->codigo)
@push('styles') @include('partials.clinico-styles') @endpush
@section('content')
@php $p = $cita->paciente; @endphp
<div class="page-header">
    <div>
        <h1 class="page-title">🩺 Atención — {{ $p->nombre_completo }}</h1>
        <p class="page-subtitle">Cita {{ $cita->codigo }} · {{ $cita->fecha->format('d/m/Y') }} {{ substr($cita->hora_inicio, 0, 5) }} · {{ $cita->especialidad->nombre }} · Inicio de atención: {{ now()->format('H:i') }}</p>
    </div>
    <a href="{{ route('consultas.index') }}" class="btn btn-light">&larr; Volver</a>
</div>

@if($errors->any())
    <div class="notice notice-err">@foreach($errors->all() as $e) <div>• {{ $e }}</div> @endforeach</div>
@endif

<!-- Resumen clínico (<<extend>> CU28) -->
@if($p->alergias->isNotEmpty())
    <div class="notice notice-err">⚠ <strong>Alergias:</strong>
        @foreach($p->alergias as $a) {{ $a->nombre }} ({{ $a->pivot->severidad }}{{ $a->pivot->reaccion ? ' - ' . $a->pivot->reaccion : '' }}){{ !$loop->last ? ',' : '' }} @endforeach
    </div>
@endif
<div class="grid-2">
    <div class="card">
        <div class="card-title">Paciente</div>
        <div class="row-detail"><span class="lbl">Código / CI</span><span class="val">{{ $p->codigo }} · {{ $p->persona->ci ?? '—' }}</span></div>
        <div class="row-detail"><span class="lbl">Edad / sexo</span><span class="val">{{ $p->fecha_nacimiento ? \Carbon\Carbon::parse($p->fecha_nacimiento)->age . ' años' : '—' }} · {{ $p->sexo }}</span></div>
        <div class="row-detail"><span class="lbl">Tipo de sangre</span><span class="val">{{ $p->tipo_sangre ?: '—' }}</span></div>
        <div class="row-detail"><span class="lbl">Motivo de la cita</span><span class="val">{{ $cita->motivo ?: '—' }}</span></div>
    </div>
    <div class="card">
        <div class="card-title">Antecedentes y medicación habitual</div>
        @forelse($p->antecedentes as $an)
            <div class="row-detail"><span class="lbl">{{ $an->tipo }}</span><span class="val">{{ $an->descripcion }}</span></div>
        @empty
            <p class="muted">Sin antecedentes registrados.</p>
        @endforelse
        @foreach($p->medicamentos as $m)
            <div class="row-detail"><span class="lbl">Medicación</span><span class="val">{{ $m->nombre_comercial }} {{ $m->pivot->dosis }}</span></div>
        @endforeach
        @if(Route::has('antecedentes.index'))
            <a href="{{ route('antecedentes.index', ['paciente' => $p->id]) }}" class="act act-info" style="margin-top:8px">+ Alergias y antecedentes (CU24)</a>
        @endif
    </div>
</div>

<form action="{{ route('consultas.store') }}" method="POST" class="card">
    @csrf
    <input type="hidden" name="cita_id" value="{{ $cita->id }}">
    <div class="card-title">Signos vitales</div>
    <div class="form-grid">
        <div class="form-group"><label class="form-label" for="peso">Peso (kg)</label><input type="number" step="0.01" min="0.5" max="400" name="peso" id="peso" class="form-control" value="{{ old('peso') }}"></div>
        <div class="form-group"><label class="form-label" for="presion_arterial">Presión arterial</label><input type="text" name="presion_arterial" id="presion_arterial" class="form-control" placeholder="120/80" value="{{ old('presion_arterial') }}"></div>
        <div class="form-group"><label class="form-label" for="temperatura">Temperatura (°C)</label><input type="number" step="0.1" min="30" max="45" name="temperatura" id="temperatura" class="form-control" value="{{ old('temperatura') }}"></div>
    </div>
    <div class="card-title" style="margin-top:18px">Consulta</div>
    <div class="form-grid">
        <div class="form-group full"><label class="form-label" for="sintomas">Síntomas</label><textarea name="sintomas" id="sintomas" rows="2" class="form-control">{{ old('sintomas') }}</textarea></div>
        <div class="form-group full"><label class="form-label" for="diagnostico">Diagnóstico <span style="color:#ef4444">*</span></label><textarea name="diagnostico" id="diagnostico" rows="2" class="form-control" required>{{ old('diagnostico') }}</textarea>@error('diagnostico')<span class="invalid-feedback">{{ $message }}</span>@enderror</div>
        <div class="form-group full"><label class="form-label" for="indicaciones">Indicaciones</label><textarea name="indicaciones" id="indicaciones" rows="2" class="form-control">{{ old('indicaciones') }}</textarea></div>
        <div class="form-group full"><label class="form-label" for="observaciones">Observaciones</label><textarea name="observaciones" id="observaciones" rows="2" class="form-control">{{ old('observaciones') }}</textarea></div>
    </div>
    <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:18px">
        <a href="{{ route('consultas.index') }}" class="btn btn-light">Cancelar</a>
        <button type="submit" class="btn btn-primary">✓ Finalizar atención</button>
    </div>
</form>
@endsection
