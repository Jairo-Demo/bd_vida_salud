@extends('layouts.app')
@section('title', 'Bloqueo de Agenda')
@push('styles') @include('partials.clinico-styles') @endpush
@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">⛔ {{ $bloqueo->tipo }} — {{ $bloqueo->descripcion_periodo }}</h1>
        <p class="page-subtitle">{{ $bloqueo->medico ? 'Dr(a). ' . ($bloqueo->medico->persona->nombre_completo ?? '') : 'Bloqueo general (todos los médicos)' }} · {{ $bloqueo->motivo }} · Registrado por {{ $bloqueo->usuario->name ?? '—' }}</p>
    </div>
    <div style="display:flex; gap:8px">
        <a href="{{ route('bloqueos.index') }}" class="btn btn-light">&larr; Bloqueos</a>
        <form action="{{ route('bloqueos.estado', $bloqueo) }}" method="POST">@csrf @method('PATCH')
            <button class="btn {{ $bloqueo->estado ? 'btn-danger' : 'btn-primary' }}">{{ $bloqueo->estado ? 'Desactivar bloqueo' : 'Activar bloqueo' }}</button>
        </form>
    </div>
</div>

@if(!$bloqueo->estado)
    <div class="notice notice-info">El bloqueo está inactivo: las fichas del período se ofrecen normalmente.</div>
@endif

<div class="card" style="padding:0; overflow-x:auto">
    <div class="card-title" style="padding:16px 18px 0">Citas activas en el período ({{ $citas->count() }})</div>
    <table class="data-table">
        <thead><tr><th>Cita</th><th>Fecha</th><th>Hora</th><th>Paciente</th><th>Médico</th><th>Estado</th><th style="text-align:right">Acción</th></tr></thead>
        <tbody>
        @forelse($citas as $c)
            <tr>
                <td><a href="{{ route('citas.show', $c) }}" style="color:#0284c7; font-weight:600; text-decoration:none">{{ $c->codigo }}</a></td>
                <td>{{ $c->fecha->format('d/m/Y') }}</td>
                <td>{{ substr($c->hora_inicio, 0, 5) }}</td>
                <td>{{ $c->paciente->nombre_completo }}</td>
                <td>Dr(a). {{ $c->medico->persona->nombre_completo ?? '' }}</td>
                <td><span class="badge b-off">{{ $c->estadoCita->nombre }}</span></td>
                <td style="text-align:right"><a href="{{ route('citas.reprogramar', $c) }}" class="act act-warn">🔁 Reprogramar</a></td>
            </tr>
        @empty
            <tr><td colspan="7" style="text-align:center; color:#64748b; padding:22px">No hay citas activas en el período del bloqueo.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
