@extends('layouts.app')
@section('title', 'Consulta de la cita ' . $consulta->cita->codigo)
@push('styles') @include('partials.clinico-styles') @endpush
@section('content')
@php $c = $consulta->cita; $esMedico = (Auth::user()->rol->nombre ?? '') === 'Medico'; @endphp
<div class="page-header">
    <div>
        <h1 class="page-title">📋 Consulta — {{ $c->paciente->nombre_completo }}</h1>
        <p class="page-subtitle">Cita {{ $c->codigo }} · {{ $c->fecha->format('d/m/Y') }} · {{ substr($consulta->hora_inicio_real, 0, 5) }} – {{ substr($consulta->hora_fin_real, 0, 5) }} · Dr(a). {{ $c->medico->persona->nombre_completo ?? '' }} · {{ $c->especialidad->nombre }}</p>
    </div>
    <div style="display:flex; gap:8px; flex-wrap:wrap">
        @if($esMedico)
            <a href="{{ route('consultas.index') }}" class="btn btn-light">&larr; Mis consultas</a>
            @if(Route::has('antecedentes.index'))
                <a href="{{ route('antecedentes.index', ['paciente' => $c->paciente_id, 'consulta' => $consulta->id]) }}" class="btn btn-light">+ Alergias / antecedentes</a>
            @endif
        @endif
        @if($consulta->receta && Route::has('recetas.show'))
            <a href="{{ route('recetas.show', $consulta->receta) }}" class="btn btn-primary">💊 Ver receta {{ $consulta->receta->codigo }}</a>
        @elseif($esMedico && Route::has('recetas.create'))
            <a href="{{ route('recetas.create', ['consulta' => $consulta->id]) }}" class="btn btn-primary">💊 Emitir receta (CU25)</a>
        @endif
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-title">Signos vitales</div>
        <div class="row-detail"><span class="lbl">Peso</span><span class="val">{{ $consulta->peso ? $consulta->peso . ' kg' : '—' }}</span></div>
        <div class="row-detail"><span class="lbl">Presión arterial</span><span class="val">{{ $consulta->presion_arterial ?: '—' }}</span></div>
        <div class="row-detail"><span class="lbl">Temperatura</span><span class="val">{{ $consulta->temperatura ? $consulta->temperatura . ' °C' : '—' }}</span></div>
    </div>
    <div class="card">
        <div class="card-title">Diagnóstico</div>
        <p style="font-size:14px; font-weight:600; color:#0f172a">{{ $consulta->diagnostico }}</p>
        <div class="row-detail"><span class="lbl">Síntomas</span><span class="val">{{ $consulta->sintomas ?: '—' }}</span></div>
        <div class="row-detail"><span class="lbl">Indicaciones</span><span class="val">{{ $consulta->indicaciones ?: '—' }}</span></div>
        <div class="row-detail"><span class="lbl">Observaciones</span><span class="val">{{ $consulta->observaciones ?: '—' }}</span></div>
    </div>
</div>
@endsection
