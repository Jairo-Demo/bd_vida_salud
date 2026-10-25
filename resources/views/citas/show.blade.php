@extends('layouts.app')

@section('title', 'Detalle de Cita: ' . $cita->codigo)

@push('styles')
<style>
    .appointment-header { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 24px 28px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
    .title-area { display: flex; align-items: center; gap: 16px; }
    .icon-box { width: 54px; height: 54px; background: linear-gradient(135deg, #0284c7 0%, #0d9488 100%); color: #ffffff; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; }
    .title { font-size: 20px; font-weight: 700; color: #0f172a; }
    .meta { font-size: 13px; color: #64748b; margin-top: 2px; }

    /* Estados */
    .badge-estado { display: inline-block; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; }
    .estado-programada { background: #e0f2fe; color: #0369a1; }
    .estado-confirmada { background: #dbeafe; color: #1d4ed8; }
    .estado-atendida { background: #dcfce7; color: #15803d; }
    .estado-cancelada { background: #fee2e2; color: #b91c1c; }
    .estado-inasistencia { background: #fef3c7; color: #92400e; }

    .btn-group { display: flex; gap: 8px; flex-wrap: wrap; }
    .btn-h { padding: 8px 14px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; border: 1px solid transparent; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
    .btn-back { background: #f1f5f9; color: #475569; }
    .btn-reschedule { background: #fef3c7; color: #92400e; border-color: #fde68a; }
    .btn-confirm { background: #dbeafe; color: #1d4ed8; border-color: #bfdbfe; }
    .btn-attend { background: #dcfce7; color: #15803d; border-color: #bbf7d0; }
    .btn-cancel-appt { background: #fee2e2; color: #b91c1c; border-color: #fecaca; }

    /* Grilla de Datos */
    .grid-2 { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; margin-bottom: 24px; }
    .card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 22px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
    .card-title { font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #0284c7; margin-bottom: 14px; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px; }
    .row-detail { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 13.5px; }
    .lbl { color: #64748b; font-weight: 500; }
    .val { color: #0f172a; font-weight: 600; text-align: right; }

    /* Linea de Tiempo del Historial de Estados (RF10) */
    .timeline { position: relative; padding-left: 24px; margin-top: 14px; }
    .timeline::before { content: ''; position: absolute; left: 7px; top: 0; bottom: 0; width: 2px; background: #e2e8f0; }
    .timeline-item { position: relative; margin-bottom: 20px; }
    .timeline-dot { position: absolute; left: -24px; top: 4px; width: 16px; height: 16px; border-radius: 50%; background: #0284c7; border: 3px solid #fff; box-shadow: 0 0 0 1px #0284c7; }
    .timeline-header { font-size: 13px; font-weight: 700; color: #0f172a; display: flex; gap: 8px; align-items: center; }
    .timeline-date { font-size: 11.5px; color: #64748b; font-weight: 500; }
    .timeline-body { font-size: 12.5px; color: #475569; margin-top: 4px; background: #f8fafc; padding: 8px 12px; border-radius: 8px; }
</style>
@endpush

@section('content')

@php
    $estadoNom = strtolower($cita->estadoCita->nombre ?? 'programada');
    $badgeClass = match($estadoNom) {
        'programada' => 'estado-programada',
        'confirmada' => 'estado-confirmada',
        'atendida' => 'estado-atendida',
        'cancelada' => 'estado-cancelada',
        'inasistencia' => 'estado-inasistencia',
        default => 'estado-programada'
    };
@endphp

<!-- Cabecera de la Cita -->
<div class="appointment-header">
    <div class="title-area">
        <div class="icon-box">📅</div>
        <div>
            <h1 class="title">Cita Médica {{ $cita->codigo }}</h1>
            <div class="meta">
                <span>Registrada el {{ \Carbon\Carbon::parse($cita->fecha_creacion)->format('d/m/Y H:i') }}</span>
                <span>•</span>
                <span>Por: <strong>{{ $cita->usuarioRegistro->name ?? 'Sistema' }}</strong></span>
            </div>
        </div>
    </div>

    <!-- Barra de Acciones de Ciclo 1 (CU15: Comprobante) -->
    <div class="btn-group">
        <a href="{{ route('citas.index') }}" class="btn-h btn-back">&larr; Volver a la Lista</a>
        <button type="button" onclick="window.print()" class="btn-h" style="background: #0284c7; color: #fff;">🖨️ Imprimir Comprobante</button>
    </div>
</div>

@if($cita->motivo_cancelacion)
    <div style="background-color: #fef2f2; border: 1px solid #fecaca; border-radius: 12px; padding: 16px 20px; margin-bottom: 24px; color: #991b1b;">
        <h3 style="font-size: 14px; font-weight: 700; margin-bottom: 2px;">Cita Médica Cancelada (RN09 / RN11)</h3>
        <p style="font-size: 13px;"><strong>Motivo de cancelación registrado:</strong> {{ $cita->motivo_cancelacion }}</p>
    </div>
@endif

<!-- Informacion de la Cita -->
<div class="grid-2">
    <!-- Ficha del Paciente y Cita -->
    <div class="card">
        <div class="card-title">1. Datos de la Consulta y Paciente</div>
        <div class="row-detail">
            <span class="lbl">Estado Actual:</span>
            <span class="val"><span class="badge-estado {{ $badgeClass }}">{{ $cita->estadoCita->nombre }}</span></span>
        </div>
        <div class="row-detail">
            <span class="lbl">Paciente:</span>
            <span class="val"><strong>{{ $cita->paciente->nombre_completo }}</strong></span>
        </div>
        <div class="row-detail">
            <span class="lbl">Documento (CI):</span>
            <span class="val">{{ $cita->paciente->persona->ci ?? '—' }}</span>
        </div>
        <div class="row-detail">
            <span class="lbl">Modalidad:</span>
            <span class="val">{{ $cita->modalidad === 'Virtual' ? '💻 Consulta Virtual (Telemedicina)' : '🏥 Consulta Presencial' }}</span>
        </div>
        <div class="row-detail">
            <span class="lbl">Motivo de Consulta:</span>
            <span class="val" style="text-align: right; max-width: 60%;">{{ $cita->motivo ?: 'Sin motivo registrado.' }}</span>
        </div>
    </div>

    <!-- Ficha del Medico y Horario -->
    <div class="card">
        <div class="card-title">2. Asignación Médica y Horario</div>
        <div class="row-detail">
            <span class="lbl">Médico:</span>
            <span class="val"><strong>Dr(a). {{ $cita->medico->persona->nombre_completo ?? '—' }}</strong></span>
        </div>
        <div class="row-detail">
            <span class="lbl">Especialidad:</span>
            <span class="val">{{ $cita->especialidad->nombre }}</span>
        </div>
        <div class="row-detail">
            <span class="lbl">Fecha Reservada:</span>
            <span class="val" style="color: #0284c7; font-size: 15px;">{{ \Carbon\Carbon::parse($cita->fecha)->format('d/m/Y') }}</span>
        </div>
        <div class="row-detail">
            <span class="lbl">Horario del Turno:</span>
            <span class="val">{{ substr($cita->hora_inicio, 0, 5) }} &ndash; {{ substr($cita->hora_fin, 0, 5) }}</span>
        </div>
        <div class="row-detail">
            <span class="lbl">Ficha de Atención:</span>
            <span class="val">
                @if($cita->ficha)
                    <strong style="color: #0284c7;">Ficha #{{ $cita->ficha->numero }}</strong>
                @else
                    —
                @endif
            </span>
        </div>
    </div>
</div>

<!-- Trazabilidad: Historial Inmutable de Estados (RF10 / CU16) -->
<div class="card">
    <div class="card-title">3. Trazabilidad Histórica de Estados (RF10 / CU16)</div>
    <p style="font-size: 13px; color: #64748b; margin-bottom: 16px;">Registro inmutable de todas las transiciones y reprogramaciones de la cita.</p>

    <div class="timeline">
        @forelse($cita->historialEstados as $h)
            <div class="timeline-item">
                <div class="timeline-dot"></div>
                <div class="timeline-header">
                    <span>
                        @if($h->estadoAnterior)
                            {{ $h->estadoAnterior->nombre }} &rarr;
                        @endif
                        <strong>{{ $h->estadoNuevo->nombre }}</strong>
                    </span>
                    <span class="timeline-date">
                        — {{ \Carbon\Carbon::parse($h->fecha_hora)->format('d/m/Y H:i:s') }}
                        @if($h->usuario)
                            (por {{ $h->usuario->name }})
                        @endif
                    </span>
                </div>
                <div class="timeline-body">
                    {{ $h->motivo }}
                </div>
            </div>
        @empty
            <p style="color: #64748b; font-size: 13px;">No hay transiciones registradas.</p>
        @endforelse
    </div>
</div>

@endsection
