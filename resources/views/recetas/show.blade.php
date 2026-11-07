@extends('layouts.app')
@section('title', 'Receta ' . $receta->codigo)
@push('styles')
@include('partials.clinico-styles')
<style>
    .rx { background:#fff; border:1px solid #e2e8f0; border-radius:14px; padding:30px 34px; max-width: 860px; margin: 0 auto; }
    .rx-head { display:flex; justify-content:space-between; border-bottom: 2px solid #0284c7; padding-bottom: 12px; margin-bottom: 16px; }
    .rx-brand { font-size: 20px; font-weight: 800; color:#0284c7; }
    .rx-code { text-align:right; font-size: 13px; color:#475569; }
    .rx-code b { font-size: 18px; color:#0f172a; }
    .rx-sign { margin-top: 48px; display:flex; justify-content:flex-end; }
    .rx-sign div { border-top: 1px solid #334155; width: 260px; text-align:center; padding-top: 6px; font-size: 12.5px; color:#334155; }
    @media print {
        .navbar, .footer, .no-print, .alert { display: none !important; }
        body { background: #fff; } .main-wrapper { margin: 0; padding: 0; } .rx { border: none; }
    }
</style>
@endpush
@section('content')
@php $p = $cita->paciente; @endphp
<div class="page-header no-print" style="max-width:860px; margin: 0 auto 16px">
    <a href="{{ (Auth::user()->rol->nombre ?? '') === 'Medico' ? route('consultas.show', $receta->consulta) : url()->previous() }}" class="btn btn-light">&larr; Volver</a>
    <button onclick="window.print()" class="btn btn-primary">🖨️ Imprimir / Guardar PDF</button>
</div>
<div class="rx">
    <div class="rx-head">
        <div><div class="rx-brand">Clínica Vida Salud</div><div class="muted">Receta médica</div></div>
        <div class="rx-code">Receta <b>{{ $receta->codigo }}</b><br>Fecha: {{ $receta->fecha_emision->format('d/m/Y') }}<br>Cita: {{ $cita->codigo }}</div>
    </div>
    <div class="grid-2" style="gap:10px 30px">
        <div>
            <div class="row-detail"><span class="lbl">Paciente</span><span class="val">{{ $p->nombre_completo }}</span></div>
            <div class="row-detail"><span class="lbl">CI / Código</span><span class="val">{{ $p->persona->ci ?? '—' }} · {{ $p->codigo }}</span></div>
        </div>
        <div>
            <div class="row-detail"><span class="lbl">Médico</span><span class="val">Dr(a). {{ $cita->medico->persona->nombre_completo ?? '' }}</span></div>
            <div class="row-detail"><span class="lbl">Especialidad</span><span class="val">{{ $cita->especialidad->nombre }}</span></div>
        </div>
    </div>
    <div class="row-detail" style="margin-top:8px"><span class="lbl">Diagnóstico</span><span class="val">{{ $receta->consulta->diagnostico }}</span></div>

    <table class="data-table" style="margin-top:18px">
        <thead><tr><th>#</th><th>Medicamento</th><th>Dosis</th><th>Frecuencia</th><th>Duración</th><th>Cant.</th><th>Indicaciones</th></tr></thead>
        <tbody>
        @foreach($receta->detalles as $i => $d)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td><strong>{{ $d->medicamento->nombre_comercial }}</strong><br><span class="muted">{{ $d->medicamento->principio_activo }} · {{ $d->medicamento->presentacion }}</span></td>
                <td>{{ $d->dosis }}</td><td>{{ $d->frecuencia }}</td><td>{{ $d->duracion_tratamiento ?: '—' }}</td><td>{{ $d->cantidad }}</td><td>{{ $d->indicaciones ?: '—' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    @if($receta->observaciones)
        <p style="margin-top:14px; font-size:13px; white-space: pre-line"><strong>Observaciones:</strong> {{ $receta->observaciones }}</p>
    @endif
    <div class="rx-sign"><div>Firma y sello<br>Dr(a). {{ $cita->medico->persona->nombre_completo ?? '' }}</div></div>
</div>
@endsection
