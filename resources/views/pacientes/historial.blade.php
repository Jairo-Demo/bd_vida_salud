@extends('layouts.app')
@section('title', 'Historial de Citas')
@push('styles')
@include('partials.clinico-styles')
<style>
    .estado-programada { background: #e0f2fe; color: #0369a1; } .estado-confirmada { background: #dbeafe; color: #1d4ed8; }
    .estado-atendida { background: #dcfce7; color: #15803d; } .estado-cancelada { background: #fee2e2; color: #b91c1c; }
    .estado-inasistencia { background: #fef3c7; color: #92400e; }
    .kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 10px; margin-bottom: 18px; }
    .kpi { background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 12px; }
    .kpi b { font-size: 20px; color: #0f172a; display: block; }
</style>
@endpush
@section('content')
@if(!$paciente)
    @include('partials.selector-pacientes', [
        'pacientes' => $pacientes, 'buscar' => $buscar, 'ruta' => 'historial.index',
        'titulo' => '📜 Historial de Citas (P2)', 'descripcion' => 'Busque al paciente por CI, código o nombre (CU07) para ver su historial de citas (CU08).',
        'accion' => 'Ver historial',
    ])
@else
    <div class="page-header">
        <div>
            <h1 class="page-title">📜 Historial de Citas — {{ $paciente->nombre_completo }}</h1>
            <p class="page-subtitle">{{ $paciente->codigo }} · CI {{ $paciente->persona->ci ?? '—' }} (CU08)</p>
        </div>
        <div style="display:flex; gap:8px">
            @if(!$esPaciente)
                <a href="{{ route('historial.index') }}" class="btn btn-light">&larr; Buscar otro paciente</a>
                <a href="{{ route('pacientes.show', $paciente) }}" class="btn btn-light">Ficha del paciente</a>
            @endif
            <a href="{{ route('citas.create', $esPaciente ? [] : ['paciente_id' => $paciente->id]) }}" class="btn btn-primary">+ Nueva cita</a>
        </div>
    </div>

    @if($errors->any())
        <div class="notice notice-err">@foreach($errors->all() as $e) <div>• {{ $e }}</div> @endforeach</div>
    @endif

    <div class="kpis">
        @foreach(['Programada', 'Confirmada', 'Atendida', 'Cancelada', 'Inasistencia'] as $n)
            <div class="kpi"><b>{{ $resumen[$n] ?? 0 }}</b><span class="badge estado-{{ strtolower($n) }}">{{ $n }}</span></div>
        @endforeach
    </div>

    <form method="GET" action="{{ route('historial.index') }}" class="card">
        @if(!$esPaciente)<input type="hidden" name="paciente" value="{{ $paciente->id }}">@endif
        <div class="form-grid">
            <div class="form-group"><label class="form-label">Estado</label>
                <select name="estado_id" class="form-control"><option value="">Todos</option>
                    @foreach($estados as $e)<option value="{{ $e->id }}" {{ (string) request('estado_id') === (string) $e->id ? 'selected' : '' }}>{{ $e->nombre }}</option>@endforeach
                </select></div>
            <div class="form-group"><label class="form-label">Desde</label><input type="date" name="desde" class="form-control" value="{{ request('desde') }}"></div>
            <div class="form-group"><label class="form-label">Hasta</label><input type="date" name="hasta" class="form-control" value="{{ request('hasta') }}"></div>
            <div class="form-group" style="justify-content:flex-end; flex-direction:row; gap:8px; align-items:flex-end">
                <button class="btn btn-primary">Filtrar</button>
                <a href="{{ route('historial.index', $esPaciente ? [] : ['paciente' => $paciente->id]) }}" class="btn btn-light">Limpiar</a>
            </div>
        </div>
    </form>

    <div class="card" style="padding:0; overflow-x:auto">
        <table class="data-table">
            <thead><tr><th>Fecha</th><th>Hora</th><th>Código</th><th>Médico</th><th>Especialidad</th><th>Ficha</th><th>Estado</th><th>Registrada</th><th style="text-align:right">Acción</th></tr></thead>
            <tbody>
            @forelse($citas as $c)
                <tr>
                    <td>{{ $c->fecha->format('d/m/Y') }}</td>
                    <td>{{ substr($c->hora_inicio, 0, 5) }}</td>
                    <td><strong>{{ $c->codigo }}</strong></td>
                    <td>Dr(a). {{ $c->medico->persona->nombre_completo ?? '' }}</td>
                    <td>{{ $c->especialidad->nombre }}</td>
                    <td>#{{ $c->ficha->numero ?? '—' }}</td>
                    <td><span class="badge estado-{{ strtolower($c->estadoCita->nombre) }}">{{ $c->estadoCita->nombre }}</span></td>
                    <td>{{ $c->fecha_creacion ? $c->fecha_creacion->format('d/m/Y') : '—' }}</td>
                    <td style="text-align:right"><a href="{{ route('citas.show', $c) }}" class="act act-info">Detalle</a></td>
                </tr>
            @empty
                <tr><td colspan="9" style="text-align:center; color:#64748b; padding:22px">El paciente no tiene citas registradas{{ request()->hasAny(['estado_id', 'desde', 'hasta']) ? ' con estos filtros' : '' }}.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div style="padding:10px 16px">{{ $citas->links() }}</div>
    </div>
@endif
@endsection
