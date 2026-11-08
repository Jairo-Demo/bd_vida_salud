@extends('layouts.app')
@section('title', 'Reportes')
@push('styles')
@include('partials.clinico-styles')
<style>
    .chart { display: flex; flex-direction: column; gap: 8px; }
    .bar-row { display: grid; grid-template-columns: 210px 1fr 60px; gap: 10px; align-items: center; font-size: 12.5px; }
    .bar-lbl { color: #334155; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .bar-bg { background: #f1f5f9; border-radius: 6px; height: 18px; overflow: hidden; }
    .bar { height: 100%; border-radius: 6px; background: linear-gradient(90deg, #0284c7, #0d9488); }
    .bar-val { text-align: right; font-weight: 700; color: #0f172a; }
    .tot td { font-weight: 700; background: #f8fafc; border-top: 2px solid #e2e8f0; }
</style>
@endpush
@section('content')
@php $q = array_filter($filtros, fn ($v) => $v !== null && $v !== ''); @endphp
<div class="page-header">
    <div>
        <h1 class="page-title">📊 Reportes (P6)</h1>
        <p class="page-subtitle">Citas por fecha, médico, especialidad y estado, agenda, pacientes registrados y resumen de asistencia (CU21). Exportación a PDF o CSV/Excel (CU22).</p>
    </div>
</div>

@if($errors->any())
    <div class="notice notice-err">@foreach($errors->all() as $e) <div>• {{ $e }}</div> @endforeach</div>
@endif

<form method="GET" action="{{ route('reportes.index') }}" class="card">
    <input type="hidden" name="generar" value="1">
    <div class="form-grid">
        <div class="form-group" style="grid-column: span 2"><label class="form-label">Tipo de reporte *</label>
            <select name="tipo" class="form-control">
                @foreach($tipos as $k => $t)<option value="{{ $k }}" {{ $filtros['tipo'] === $k ? 'selected' : '' }}>{{ $t }}</option>@endforeach
            </select></div>
        <div class="form-group"><label class="form-label">Desde *</label><input type="date" name="desde" class="form-control" required value="{{ $filtros['desde'] }}"></div>
        <div class="form-group"><label class="form-label">Hasta *</label><input type="date" name="hasta" class="form-control" required value="{{ $filtros['hasta'] }}"></div>
        <div class="form-group"><label class="form-label">Médico</label>
            <select name="medico_id" class="form-control"><option value="">Todos</option>
                @foreach($medicos as $m)<option value="{{ $m->id }}" {{ (string) $filtros['medico_id'] === (string) $m->id ? 'selected' : '' }}>Dr(a). {{ $m->persona->nombre_completo ?? $m->codigo }}</option>@endforeach
            </select></div>
        <div class="form-group"><label class="form-label">Especialidad</label>
            <select name="especialidad_id" class="form-control"><option value="">Todas</option>
                @foreach($especialidades as $e)<option value="{{ $e->id }}" {{ (string) $filtros['especialidad_id'] === (string) $e->id ? 'selected' : '' }}>{{ $e->nombre }}</option>@endforeach
            </select></div>
        <div class="form-group"><label class="form-label">Estado</label>
            <select name="estado_id" class="form-control"><option value="">Todos</option>
                @foreach($estados as $e)<option value="{{ $e->id }}" {{ (string) $filtros['estado_id'] === (string) $e->id ? 'selected' : '' }}>{{ $e->nombre }}</option>@endforeach
            </select></div>
        <div class="form-group" style="justify-content:flex-end"><button class="btn btn-primary">Generar</button></div>
    </div>
</form>

@if($reporte)
    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px; flex-wrap:wrap; margin-bottom:14px">
            <div>
                <div style="font-size:17px; font-weight:700; color:#0f172a">{{ $reporte['titulo'] }}</div>
                <div class="muted">{{ $reporte['subtitulo'] }}</div>
            </div>
            <div style="display:flex; gap:8px">
                <a href="{{ route('reportes.exportar', ['formato' => 'pdf'] + $q) }}" class="btn btn-light" target="_blank">📄 Exportar PDF</a>
                <a href="{{ route('reportes.exportar', ['formato' => 'csv'] + $q) }}" class="btn btn-light">📗 Exportar Excel (CSV)</a>
            </div>
        </div>

        @if(empty($reporte['filas']))
            <div class="notice notice-info">No existen datos para los filtros elegidos: el reporte se muestra con totales en cero.</div>
        @endif

        @php $max = max(array_merge([1], array_values($reporte['grafico']))); @endphp
        @if(!empty($reporte['grafico']))
            <div class="card-title">Gráfico resumen</div>
            <div class="chart" style="margin-bottom:20px">
                @foreach(array_slice($reporte['grafico'], 0, 15, true) as $lbl => $val)
                    <div class="bar-row">
                        <div class="bar-lbl" title="{{ $lbl }}">{{ $lbl }}</div>
                        <div class="bar-bg"><div class="bar" style="width: {{ round($val * 100 / $max, 1) }}%"></div></div>
                        <div class="bar-val">{{ $val }}</div>
                    </div>
                @endforeach
            </div>
        @endif

        <div style="overflow-x:auto">
            <table class="data-table">
                <thead><tr>@foreach($reporte['columnas'] as $c)<th>{{ $c }}</th>@endforeach</tr></thead>
                <tbody>
                @forelse($reporte['filas'] as $f)
                    <tr>@foreach($reporte['columnas'] as $c)<td>{{ $f[$c] ?? '' }}</td>@endforeach</tr>
                @empty
                    <tr><td colspan="{{ count($reporte['columnas']) }}" class="muted" style="text-align:center">Sin registros.</td></tr>
                @endforelse
                <tr class="tot">@foreach($reporte['columnas'] as $c)<td>{{ $reporte['totales'][$c] ?? '' }}</td>@endforeach</tr>
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
