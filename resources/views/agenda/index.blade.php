@extends('layouts.app')

@section('title', 'Agenda del Día')

@push('styles')
<style>
    .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 16px; }
    .page-title { font-size: 22px; font-weight: 700; color: #0f172a; }
    .page-subtitle { font-size: 13px; color: #64748b; margin-top: 2px; }
    .card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
    .card-title { font-size: 12.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #0284c7; margin-bottom: 12px; }
    .filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; align-items: end; }
    .filter-label { font-size: 11.5px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 4px; display: block; }
    .filter-control { width: 100%; padding: 8px 12px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 13px; background: #fff; }
    .btn-submit { background: #0f172a; color: #fff; padding: 9px 16px; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; height: 38px; }
    .btn-reset { background: #f1f5f9; color: #475569; padding: 9px 14px; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 600; height: 38px; display: inline-flex; align-items: center; }
    .kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 12px; margin-bottom: 20px; }
    .kpi { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px 14px; }
    .kpi-v { font-size: 22px; font-weight: 700; color: #0f172a; }
    .kpi-l { font-size: 11.5px; color: #64748b; font-weight: 600; }
    .week { display: grid; grid-template-columns: repeat(7, 1fr); gap: 8px; }
    .day { border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 8px; text-align: center; text-decoration: none; color: #334155; font-size: 12px; }
    .day.sel { border-color: #0284c7; background: #e0f2fe; }
    .day.off { background: #f8fafc; color: #94a3b8; }
    .day.blk { background: #fef2f2; color: #b91c1c; }
    .day b { display: block; font-size: 12.5px; color: #0f172a; }
    .data-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .data-table th { background: #f8fafc; color: #475569; font-weight: 700; padding: 10px 12px; border-bottom: 1px solid #e2e8f0; font-size: 11px; text-transform: uppercase; text-align: left; }
    .data-table td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    .row-libre td { color: #94a3b8; }
    .badge-estado { display: inline-block; padding: 3px 9px; border-radius: 6px; font-size: 11px; font-weight: 700; }
    .estado-programada { background: #e0f2fe; color: #0369a1; }
    .estado-confirmada { background: #dbeafe; color: #1d4ed8; }
    .estado-atendida { background: #dcfce7; color: #15803d; }
    .estado-cancelada { background: #fee2e2; color: #b91c1c; }
    .estado-inasistencia { background: #fef3c7; color: #92400e; }
    .act { display: inline-block; border: none; cursor: pointer; font-size: 11.5px; font-weight: 600; padding: 5px 9px; border-radius: 6px; margin: 1px 2px; text-decoration: none; }
    .act-ok { background: #dcfce7; color: #166534; }
    .act-warn { background: #fef3c7; color: #92400e; }
    .act-info { background: #e0f2fe; color: #0369a1; }
    .act-att { background: #ede9fe; color: #5b21b6; }
    .notice { padding: 12px 16px; border-radius: 10px; font-size: 13px; margin-bottom: 16px; }
    .notice-warn { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; }
    .notice-err { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
    .notice-info { background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; }
</style>
@endpush

@section('content')
@php
    $puedeGestionar = in_array(Auth::user()->rol->nombre, ['Administrador', 'Recepcionista']);
    $badge = fn ($n) => 'estado-' . strtolower($n);
    $fechaC = \Carbon\Carbon::parse($fecha);
    $vencida = fn ($c) => \Carbon\Carbon::parse($c->fecha->toDateString() . ' ' . $c->hora_inicio)->isPast();
@endphp

<div class="page-header">
    <div>
        <h1 class="page-title">🗓️ Agenda del Día (P5)</h1>
        <p class="page-subtitle">
            {{ $dia }} {{ $fechaC->format('d/m/Y') }}
            @if($medico) · Dr(a). {{ $medico->persona->nombre_completo ?? $medico->codigo }} @endif
            — Confirmar asistencia (CU18), registrar inasistencia (CU19) y consultar la agenda (CU20).
        </p>
    </div>
</div>

@if($errors->any())
    <div class="notice notice-err">@foreach($errors->all() as $e) <div>• {{ $e }}</div> @endforeach</div>
@endif

<!-- Filtros -->
<div class="card">
    <form action="{{ route('agenda.index') }}" method="GET" class="filter-grid">
        @if(!$esMedico)
            <div>
                <label class="filter-label" for="medico_id">Médico</label>
                <select name="medico_id" id="medico_id" class="filter-control">
                    <option value="">Todos los médicos</option>
                    @foreach($medicos as $m)
                        <option value="{{ $m->id }}" {{ (string) $medicoId === (string) $m->id ? 'selected' : '' }}>Dr(a). {{ $m->persona->nombre_completo ?? $m->codigo }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div>
            <label class="filter-label" for="fecha">Fecha</label>
            <input type="date" name="fecha" id="fecha" class="filter-control" value="{{ $fecha }}">
        </div>
        <div>
            <label class="filter-label" for="estado_id">Estado</label>
            <select name="estado_id" id="estado_id" class="filter-control">
                <option value="">Todos</option>
                @foreach($estados as $e)
                    <option value="{{ $e->id }}" {{ (string) $estadoId === (string) $e->id ? 'selected' : '' }}>{{ $e->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div style="display:flex; gap:8px;">
            <button type="submit" class="btn-submit" style="flex:1">Ver agenda</button>
            <a href="{{ route('agenda.index', $medicoId && !$esMedico ? ['medico_id' => $medicoId] : []) }}" class="btn-reset">Hoy</a>
        </div>
    </form>
</div>

<!-- Indicadores del día -->
<div class="kpis">
    @foreach(['Programada', 'Confirmada', 'Atendida', 'Cancelada', 'Inasistencia'] as $n)
        <div class="kpi"><div class="kpi-v">{{ $conteo[$n] ?? 0 }}</div><div class="kpi-l"><span class="badge-estado {{ $badge($n) }}">{{ $n }}</span></div></div>
    @endforeach
</div>

@if($medico)
    <!-- Resumen semanal (CU20 - alterno A) -->
    <div class="card">
        <div class="card-title">Semana del médico — fichas ocupadas / libres</div>
        <div class="week">
            @foreach($semana as $d)
                <a href="{{ route('agenda.index', array_filter(['medico_id' => $esMedico ? null : $medicoId, 'fecha' => $d['fecha']])) }}"
                   class="day {{ $d['fecha'] === $fecha ? 'sel' : '' }} {{ !$d['atiende'] ? 'off' : '' }} {{ $d['bloqueado'] ? 'blk' : '' }}">
                    <b>{{ substr($d['dia'], 0, 3) }} {{ \Carbon\Carbon::parse($d['fecha'])->format('d/m') }}</b>
                    @if($d['bloqueado']) Bloqueado
                    @elseif(!$d['atiende']) No atiende
                    @else {{ $d['ocupadas'] }} ocup. · {{ $d['libres'] }} libres
                    @endif
                </a>
            @endforeach
        </div>
    </div>

    @if($bloqueo)
        <div class="notice notice-err">⛔ Agenda bloqueada en esta fecha: <strong>{{ $bloqueo->tipo }}</strong> — {{ $bloqueo->motivo }}</div>
    @endif

    @if(!$horario)
        <div class="notice notice-warn">
            El médico no atiende los días {{ $dia }}.
            @if(count($diasAtencion)) Días de atención: <strong>{{ implode(', ', $diasAtencion) }}</strong>. @endif
        </div>
    @else
        <div class="card" style="padding: 0; overflow-x: auto;">
            <div style="display:flex; justify-content:space-between; align-items:center; padding: 14px 18px;">
                <div class="card-title" style="margin:0">Jornada {{ substr($horario->hora_inicio, 0, 5) }} – {{ substr($horario->hora_fin, 0, 5) }} · fichas de {{ $horario->duracion_cita_min }} min</div>
                @if($puedeGestionar && $fechaC->lte(today()))
                    <form action="{{ route('agenda.inasistencias') }}" method="POST" onsubmit="return confirm('¿Marcar como inasistencia todas las citas vencidas no atendidas de esta jornada?')">
                        @csrf
                        <input type="hidden" name="medico_id" value="{{ $medico->id }}">
                        <input type="hidden" name="fecha" value="{{ $fecha }}">
                        <button type="submit" class="act act-warn">⚠ Marcar inasistencias vencidas</button>
                    </form>
                @endif
            </div>
            <table class="data-table">
                <thead>
                    <tr><th>Ficha</th><th>Hora</th><th>Código</th><th>Paciente</th><th>Especialidad</th><th>Motivo</th><th>Estado</th><th style="text-align:right">Acciones</th></tr>
                </thead>
                <tbody>
                    @forelse($filas as $f)
                        @php $c = $f['cita']; @endphp
                        <tr class="{{ $c ? '' : 'row-libre' }}">
                            <td><strong>#{{ $f['ficha']->numero }}</strong></td>
                            <td>{{ substr($f['ficha']->hora_inicio, 0, 5) }} – {{ substr($f['ficha']->hora_fin, 0, 5) }}</td>
                            @if($c)
                                <td><a href="{{ route('citas.show', $c) }}" style="color:#0284c7; font-weight:600; text-decoration:none">{{ $c->codigo }}</a></td>
                                <td>{{ $c->paciente->nombre_completo }}</td>
                                <td>{{ $c->especialidad->nombre }}</td>
                                <td style="max-width: 200px">{{ \Illuminate\Support\Str::limit($c->motivo, 40) ?: '—' }}</td>
                                <td><span class="badge-estado {{ $badge($c->estadoCita->nombre) }}">{{ $c->estadoCita->nombre }}</span></td>
                                <td style="text-align:right; white-space:nowrap">
                                    @if($puedeGestionar && $c->estadoCita->nombre === 'Programada' && $c->fecha->gte(today()))
                                        <form action="{{ route('citas.confirmar', $c) }}" method="POST" style="display:inline">@csrf @method('PATCH')<button class="act act-ok">✓ Confirmar</button></form>
                                    @endif
                                    @if($puedeGestionar && in_array($c->estadoCita->nombre, ['Programada', 'Confirmada']) && $vencida($c))
                                        <form action="{{ route('citas.inasistencia', $c) }}" method="POST" style="display:inline" onsubmit="return confirm('¿Registrar la inasistencia de {{ $c->codigo }}?')">@csrf @method('PATCH')<button class="act act-warn">⚠ Inasistencia</button></form>
                                    @endif
                                    @if($esMedico && $c->estadoCita->nombre === 'Confirmada' && Route::has('consultas.create'))
                                        <a href="{{ route('consultas.create', ['cita' => $c->id]) }}" class="act act-att">🩺 Atender</a>
                                    @endif
                                    <a href="{{ route('citas.show', $c) }}" class="act act-info">Ver</a>
                                </td>
                            @else
                                <td colspan="5">{{ $f['libre'] ? 'Libre' : 'Bloqueada' }}</td>
                                <td></td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="8" style="text-align:center; color:#64748b; padding: 22px">No hay citas con el filtro seleccionado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
@else
    <div class="notice notice-info">Seleccione un médico para ver su agenda por fichas. Mientras tanto se muestran todas las citas del día.</div>
    <div class="card" style="padding:0; overflow-x:auto">
        <table class="data-table">
            <thead><tr><th>Hora</th><th>Código</th><th>Paciente</th><th>Médico</th><th>Especialidad</th><th>Estado</th><th style="text-align:right">Acciones</th></tr></thead>
            <tbody>
                @forelse($citasDelDia as $c)
                    <tr>
                        <td>{{ substr($c->hora_inicio, 0, 5) }}</td>
                        <td><a href="{{ route('citas.show', $c) }}" style="color:#0284c7; font-weight:600; text-decoration:none">{{ $c->codigo }}</a></td>
                        <td>{{ $c->paciente->nombre_completo }}</td>
                        <td>Dr(a). {{ $c->medico->persona->nombre_completo ?? '' }}</td>
                        <td>{{ $c->especialidad->nombre }}</td>
                        <td><span class="badge-estado {{ $badge($c->estadoCita->nombre) }}">{{ $c->estadoCita->nombre }}</span></td>
                        <td style="text-align:right; white-space:nowrap">
                            @if($puedeGestionar && $c->estadoCita->nombre === 'Programada' && $c->fecha->gte(today()))
                                <form action="{{ route('citas.confirmar', $c) }}" method="POST" style="display:inline">@csrf @method('PATCH')<button class="act act-ok">✓ Confirmar</button></form>
                            @endif
                            @if($puedeGestionar && in_array($c->estadoCita->nombre, ['Programada', 'Confirmada']) && $vencida($c))
                                <form action="{{ route('citas.inasistencia', $c) }}" method="POST" style="display:inline" onsubmit="return confirm('¿Registrar la inasistencia de {{ $c->codigo }}?')">@csrf @method('PATCH')<button class="act act-warn">⚠ Inasistencia</button></form>
                            @endif
                            <a href="{{ route('agenda.index', ['medico_id' => $c->medico_id, 'fecha' => $fecha]) }}" class="act act-info">Agenda</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="text-align:center; color:#64748b; padding: 22px">No hay citas registradas para esta fecha.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endif
@endsection
