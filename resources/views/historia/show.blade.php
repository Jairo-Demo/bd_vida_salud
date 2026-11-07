@extends('layouts.app')
@section('title', 'Historia Clínica - ' . $paciente->nombre_completo)
@push('styles')
@include('partials.clinico-styles')
<style>
    .tl { border-left: 3px solid #bae6fd; padding-left: 20px; margin-left: 6px; }
    .ti { position: relative; margin-bottom: 18px; }
    .ti::before { content: ''; position: absolute; left: -28px; top: 4px; width: 13px; height: 13px; border-radius: 50%; background: #0284c7; border: 3px solid #fff; box-shadow: 0 0 0 1px #0284c7; }
    .ti-head { font-size: 13px; font-weight: 700; color: #0f172a; }
    .ti-body { font-size: 13px; color: #475569; background: #f8fafc; border-radius: 8px; padding: 10px 12px; margin-top: 6px; }
    @media print { .navbar, .footer, .no-print { display: none !important; } }
</style>
@endpush
@section('content')
@php $p = $paciente; $sev = fn ($s) => 'b-' . strtolower($s); $esMedico = (Auth::user()->rol->nombre ?? '') === 'Medico'; @endphp
<div class="page-header">
    <div>
        <h1 class="page-title">📚 Historia Clínica — {{ $p->nombre_completo }}</h1>
        <p class="page-subtitle">{{ $p->codigo }} · CI {{ $p->persona->ci ?? '—' }} · {{ $p->fecha_nacimiento ? \Carbon\Carbon::parse($p->fecha_nacimiento)->age . ' años' : '' }} · {{ $p->sexo }} · Tipo de sangre {{ $p->tipo_sangre ?: '—' }}</p>
    </div>
    <div class="no-print" style="display:flex; gap:8px">
        @if($esMedico)
            <a href="{{ route('historia.index') }}" class="btn btn-light">&larr; Pacientes</a>
            <a href="{{ route('antecedentes.index', ['paciente' => $p->id]) }}" class="btn btn-light">🧾 Alergias y antecedentes</a>
        @endif
        <button onclick="window.print()" class="btn btn-primary">🖨️ Imprimir / PDF</button>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-title">Alergias</div>
        @forelse($p->alergias as $a)
            <div class="row-detail"><span class="lbl">{{ $a->nombre }}</span><span class="val"><span class="badge {{ $sev($a->pivot->severidad) }}">{{ $a->pivot->severidad }}</span> {{ $a->pivot->reaccion }}</span></div>
        @empty
            <p class="muted">Sin alergias registradas.</p>
        @endforelse
        @if($p->otras_alergias)<p class="muted" style="margin-top:6px">Otras (registro inicial): {{ $p->otras_alergias }}</p>@endif
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
    </div>
</div>

<div class="card">
    <div class="card-title">Atenciones ({{ $consultas->count() }})</div>
    @if($consultas->isEmpty())
        <p class="muted">El paciente aún no tiene consultas registradas: se muestran solo sus datos y alergias.</p>
    @else
        <div class="tl">
            @foreach($consultas as $c)
                <div class="ti">
                    <div class="ti-head">{{ $c->cita->fecha->format('d/m/Y') }} · {{ substr($c->cita->hora_inicio, 0, 5) }} · Dr(a). {{ $c->cita->medico->persona->nombre_completo ?? '' }} · {{ $c->cita->especialidad->nombre }} · Cita {{ $c->cita->codigo }}</div>
                    <div class="ti-body">
                        <div><strong>Diagnóstico:</strong> {{ $c->diagnostico }}</div>
                        @if($c->sintomas)<div><strong>Síntomas:</strong> {{ $c->sintomas }}</div>@endif
                        <div><strong>Signos:</strong> PA {{ $c->presion_arterial ?: '—' }} · T {{ $c->temperatura ? $c->temperatura . ' °C' : '—' }} · Peso {{ $c->peso ? $c->peso . ' kg' : '—' }}</div>
                        @if($c->indicaciones)<div><strong>Indicaciones:</strong> {{ $c->indicaciones }}</div>@endif
                        @if($c->receta)
                            <div style="margin-top:6px"><strong>Receta {{ $c->receta->codigo }}:</strong>
                                {{ $c->receta->detalles->map(fn ($d) => $d->medicamento->nombre_comercial . ' ' . $d->dosis . ' ' . $d->frecuencia)->join('; ') }}
                                <a class="act act-info no-print" href="{{ route('recetas.show', $c->receta) }}">Ver receta</a>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
