@extends('layouts.app')
@section('title', 'Recordatorios de Citas')
@push('styles') @include('partials.clinico-styles') @endpush
@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">✉️ Recordatorios de Citas (P8)</h1>
        <p class="page-subtitle">La tarea programada envía cada hora los avisos por correo: 24 h antes de la cita, al reprogramar y al cancelar (CU31).</p>
    </div>
    <form action="{{ route('recordatorios.enviar') }}" method="POST">@csrf
        <button class="btn btn-primary">📨 Enviar pendientes ahora ({{ $vencidos }})</button>
    </form>
</div>

<div class="grid-2" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 18px">
    @foreach(['Pendiente' => 'b-moderada', 'Enviado' => 'b-on', 'Fallido' => 'b-grave'] as $e => $cls)
        <div class="card" style="margin:0"><div style="font-size:22px; font-weight:700">{{ $conteo[$e] ?? 0 }}</div><span class="badge {{ $cls }}">{{ $e }}</span></div>
    @endforeach
</div>

<div class="card" style="padding:0; overflow-x:auto">
    <form method="GET" action="{{ route('recordatorios.index') }}" style="display:flex; gap:8px; padding:14px 16px; flex-wrap:wrap">
        <select name="estado" class="form-control" style="max-width:180px">
            <option value="">Todos los estados</option>
            @foreach(['Pendiente', 'Enviado', 'Fallido'] as $e)<option {{ $estado === $e ? 'selected' : '' }}>{{ $e }}</option>@endforeach
        </select>
        <select name="tipo" class="form-control" style="max-width:200px">
            <option value="">Todos los tipos</option>
            @foreach(['Proxima cita' => 'Próxima cita', 'Reprogramacion' => 'Reprogramación', 'Cancelacion' => 'Cancelación'] as $k => $t)<option value="{{ $k }}" {{ $tipo === $k ? 'selected' : '' }}>{{ $t }}</option>@endforeach
        </select>
        <button class="btn btn-light">Filtrar</button>
    </form>
    <table class="data-table">
        <thead><tr><th>Programado</th><th>Cita</th><th>Paciente</th><th>Tipo</th><th>Mensaje</th><th>Estado</th><th>Enviado</th><th style="text-align:right">Acción</th></tr></thead>
        <tbody>
        @forelse($recordatorios as $r)
            <tr>
                <td>{{ $r->fecha_programada?->format('d/m/Y H:i') }}</td>
                <td><a href="{{ route('citas.show', $r->cita_id) }}" style="color:#0284c7; font-weight:600; text-decoration:none">{{ $r->cita->codigo ?? '' }}</a></td>
                <td>{{ $r->cita->paciente->nombre_completo ?? '' }}<br><span class="muted">{{ $r->cita->paciente->persona->correo ?? 'sin correo' }}</span></td>
                <td>{{ ['Proxima cita' => 'Próxima cita', 'Reprogramacion' => 'Reprogramación', 'Cancelacion' => 'Cancelación'][$r->tipo] ?? $r->tipo }}</td>
                <td style="max-width:280px">{{ $r->mensaje }}</td>
                <td><span class="badge {{ ['Pendiente' => 'b-moderada', 'Enviado' => 'b-on', 'Fallido' => 'b-grave'][$r->estado] ?? 'b-off' }}">{{ $r->estado }}</span></td>
                <td>{{ $r->fecha_envio?->format('d/m/Y H:i') ?? '—' }}</td>
                <td style="text-align:right">
                    @if($r->estado === 'Fallido' && !str_starts_with((string) $r->mensaje, 'Omitido:'))
                        <form action="{{ route('recordatorios.reintentar', $r) }}" method="POST">@csrf<button class="act act-warn">Reintentar</button></form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="8" style="text-align:center; color:#64748b; padding:22px">No hay recordatorios con el filtro seleccionado.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div style="padding:10px 16px">{{ $recordatorios->links() }}</div>
</div>
@endsection
