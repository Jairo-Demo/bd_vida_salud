@extends('layouts.app')
@section('title', 'Registrar Atención')
@push('styles') @include('partials.clinico-styles') @endpush
@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">🩺 Registrar Atención (P7)</h1>
        <p class="page-subtitle">Dr(a). {{ $medico->persona->nombre_completo ?? $medico->codigo }} — citas de hoy {{ today()->format('d/m/Y') }} por atender (CU23).</p>
    </div>
    <a href="{{ route('agenda.index') }}" class="btn btn-light">🗓️ Ver mi agenda</a>
</div>

<div class="card" style="padding:0; overflow-x:auto">
    <div class="card-title" style="padding: 16px 18px 0">Citas por atender hoy</div>
    <table class="data-table">
        <thead><tr><th>Hora</th><th>Código</th><th>Paciente</th><th>Especialidad</th><th>Motivo</th><th>Estado</th><th style="text-align:right">Acción</th></tr></thead>
        <tbody>
        @forelse($porAtender as $c)
            <tr>
                <td>{{ substr($c->hora_inicio, 0, 5) }}</td>
                <td>{{ $c->codigo }}</td>
                <td>{{ $c->paciente->nombre_completo }}</td>
                <td>{{ $c->especialidad->nombre }}</td>
                <td>{{ \Illuminate\Support\Str::limit($c->motivo, 40) ?: '—' }}</td>
                <td><span class="badge b-off">{{ $c->estadoCita->nombre }}</span></td>
                <td style="text-align:right"><a href="{{ route('consultas.create', ['cita' => $c->id]) }}" class="act act-att">🩺 Atender</a></td>
            </tr>
        @empty
            <tr><td colspan="7" style="text-align:center; color:#64748b; padding:22px">No tiene citas pendientes de atención para hoy.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="card" style="padding:0; overflow-x:auto">
    <div class="card-title" style="padding: 16px 18px 0">Últimas consultas registradas</div>
    <table class="data-table">
        <thead><tr><th>Fecha</th><th>Cita</th><th>Paciente</th><th>Diagnóstico</th><th>Receta</th><th style="text-align:right">Acción</th></tr></thead>
        <tbody>
        @forelse($recientes as $con)
            <tr>
                <td>{{ $con->cita->fecha->format('d/m/Y') }}</td>
                <td>{{ $con->cita->codigo }}</td>
                <td>{{ $con->cita->paciente->nombre_completo }}</td>
                <td>{{ \Illuminate\Support\Str::limit($con->diagnostico, 50) }}</td>
                <td>{{ $con->receta->codigo ?? '—' }}</td>
                <td style="text-align:right"><a href="{{ route('consultas.show', $con) }}" class="act act-info">Ver</a></td>
            </tr>
        @empty
            <tr><td colspan="6" style="text-align:center; color:#64748b; padding:22px">Aún no registró consultas.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
