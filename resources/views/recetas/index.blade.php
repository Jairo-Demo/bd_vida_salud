@extends('layouts.app')
@section('title', 'Recetas Médicas')
@push('styles') @include('partials.clinico-styles') @endpush
@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">💊 Recetas Médicas (P7)</h1>
        <p class="page-subtitle">Recetas emitidas con verificación de alergias (CU25 – CU26). Las recetas se emiten desde la consulta.</p>
    </div>
</div>

@if($sinReceta->isNotEmpty())
<div class="card" style="padding:0; overflow-x:auto">
    <div class="card-title" style="padding:16px 18px 0">Consultas recientes sin receta</div>
    <table class="data-table">
        <thead><tr><th>Fecha</th><th>Cita</th><th>Paciente</th><th>Diagnóstico</th><th style="text-align:right">Acción</th></tr></thead>
        <tbody>
        @foreach($sinReceta as $c)
            <tr>
                <td>{{ $c->cita->fecha->format('d/m/Y') }}</td><td>{{ $c->cita->codigo }}</td>
                <td>{{ $c->cita->paciente->nombre_completo }}</td><td>{{ \Illuminate\Support\Str::limit($c->diagnostico, 50) }}</td>
                <td style="text-align:right"><a href="{{ route('recetas.create', ['consulta' => $c->id]) }}" class="act act-att">💊 Emitir receta</a></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

<div class="card" style="padding:0; overflow-x:auto">
    <div class="card-title" style="padding:16px 18px 0">Recetas emitidas</div>
    <table class="data-table">
        <thead><tr><th>Código</th><th>Fecha</th><th>Paciente</th><th>Cita</th><th>Medicamentos</th><th style="text-align:right">Acción</th></tr></thead>
        <tbody>
        @forelse($recetas as $r)
            <tr>
                <td><strong>{{ $r->codigo }}</strong></td><td>{{ $r->fecha_emision->format('d/m/Y') }}</td>
                <td>{{ $r->consulta->cita->paciente->nombre_completo }}</td><td>{{ $r->consulta->cita->codigo }}</td>
                <td>{{ $r->detalles_count }}</td>
                <td style="text-align:right"><a href="{{ route('recetas.show', $r) }}" class="act act-info">Ver / imprimir</a></td>
            </tr>
        @empty
            <tr><td colspan="6" style="text-align:center; color:#64748b; padding:22px">Aún no emitió recetas.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div style="padding:10px 16px">{{ $recetas->links() }}</div>
</div>
@endsection
