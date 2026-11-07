@extends('layouts.app')
@section('title', 'Alergias y Antecedentes - ' . $paciente->nombre_completo)
@push('styles') @include('partials.clinico-styles') @endpush
@section('content')
@php $sev = fn ($s) => 'b-' . strtolower($s); @endphp
<div class="page-header">
    <div>
        <h1 class="page-title">🧾 {{ $paciente->nombre_completo }}</h1>
        <p class="page-subtitle">{{ $paciente->codigo }} · CI {{ $paciente->persona->ci ?? '—' }} · Tipo de sangre {{ $paciente->tipo_sangre ?: '—' }}
            @if($consulta) · Se asociarán a la consulta de la cita {{ $consulta->cita->codigo ?? '' }} @endif</p>
    </div>
    <div style="display:flex; gap:8px">
        @if($consulta)<a href="{{ route('consultas.show', $consulta) }}" class="btn btn-light">&larr; Volver a la consulta</a>@endif
        @if(Route::has('historia.show'))<a href="{{ route('historia.show', $paciente) }}" class="btn btn-light">📚 Historia clínica</a>@endif
    </div>
</div>

@if($errors->any())
    <div class="notice notice-err">@foreach($errors->all() as $e) <div>• {{ $e }}</div> @endforeach</div>
@endif

<div class="grid-2">
    <!-- Alergias -->
    <div class="card">
        <div class="card-title">Alergias</div>
        <form action="{{ route('antecedentes.alergias.store', $paciente) }}" method="POST" class="form-grid">
            @csrf
            <div class="form-group"><label class="form-label">Alergia (catálogo) *</label>
                <select name="alergia_id" class="form-control" required>
                    <option value="">Seleccione...</option>
                    @foreach($alergiasCatalogo as $a)<option value="{{ $a->id }}">{{ $a->nombre }}</option>@endforeach
                </select></div>
            <div class="form-group"><label class="form-label">Severidad *</label>
                <select name="severidad" class="form-control" required>
                    <option>Leve</option><option>Moderada</option><option>Grave</option>
                </select></div>
            <div class="form-group full"><label class="form-label">Reacción</label><input name="reaccion" class="form-control" placeholder="Ej. Urticaria, anafilaxia..."></div>
            <div class="form-group full" style="align-items:flex-end"><button class="btn btn-primary btn-sm">+ Agregar alergia</button></div>
        </form>
        <table class="data-table" style="margin-top:12px">
            <thead><tr><th>Alergia</th><th>Severidad</th><th>Reacción</th><th></th></tr></thead>
            <tbody>
            @forelse($paciente->alergias as $a)
                <tr>
                    <td><strong>{{ $a->nombre }}</strong></td>
                    <td><span class="badge {{ $sev($a->pivot->severidad) }}">{{ $a->pivot->severidad }}</span></td>
                    <td>{{ $a->pivot->reaccion ?: '—' }}</td>
                    <td style="text-align:right">
                        <form action="{{ route('antecedentes.alergias.destroy', [$paciente, $a]) }}" method="POST" onsubmit="return confirm('¿Retirar la alergia {{ $a->nombre }}?')">@csrf @method('DELETE')<button class="act act-del">Quitar</button></form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="muted">Sin alergias registradas.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <!-- Antecedentes -->
    <div class="card">
        <div class="card-title">Antecedentes</div>
        <form action="{{ route('antecedentes.antecedentes.store', $paciente) }}" method="POST" class="form-grid">
            @csrf
            @if($consulta)<input type="hidden" name="consulta_id" value="{{ $consulta->id }}">@endif
            <div class="form-group"><label class="form-label">Tipo *</label>
                <select name="tipo" class="form-control" required>
                    <option>Personal</option><option>Familiar</option><option value="Quirurgico">Quirúrgico</option><option value="Cronico">Crónico</option><option>Otro</option>
                </select></div>
            <div class="form-group full"><label class="form-label">Descripción *</label><input name="descripcion" class="form-control" required minlength="3" placeholder="Ej. Madre con hipertensión arterial"></div>
            <div class="form-group full" style="align-items:flex-end"><button class="btn btn-primary btn-sm">+ Agregar antecedente</button></div>
        </form>
        <table class="data-table" style="margin-top:12px">
            <thead><tr><th>Tipo</th><th>Descripción</th><th>Fecha</th><th></th></tr></thead>
            <tbody>
            @forelse($paciente->antecedentes as $an)
                <tr>
                    <td><strong>{{ $an->tipo }}</strong></td>
                    <td>{{ $an->descripcion }}</td>
                    <td>{{ $an->fecha_registro ? \Carbon\Carbon::parse($an->fecha_registro)->format('d/m/Y') : '—' }}</td>
                    <td style="text-align:right">
                        <form action="{{ route('antecedentes.antecedentes.destroy', $an) }}" method="POST" onsubmit="return confirm('¿Eliminar el antecedente?')">@csrf @method('DELETE')<button class="act act-del">Quitar</button></form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="muted">Sin antecedentes registrados.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Medicación habitual -->
<div class="card">
    <div class="card-title">Medicación habitual</div>
    <form action="{{ route('antecedentes.medicamentos.store', $paciente) }}" method="POST" class="form-grid">
        @csrf
        <div class="form-group"><label class="form-label">Medicamento *</label>
            <select name="medicamento_id" class="form-control" required>
                <option value="">Seleccione...</option>
                @foreach($medicamentosCatalogo as $m)<option value="{{ $m->id }}">{{ $m->nombre_comercial }} — {{ $m->presentacion }}</option>@endforeach
            </select></div>
        <div class="form-group"><label class="form-label">Dosis</label><input name="dosis" class="form-control" placeholder="1 comprimido al día"></div>
        <div class="form-group"><label class="form-label">Fecha de inicio</label><input type="date" name="fecha_inicio" max="{{ today()->toDateString() }}" class="form-control"></div>
        <div class="form-group" style="justify-content:flex-end"><button class="btn btn-primary btn-sm">+ Agregar</button></div>
    </form>
    <table class="data-table" style="margin-top:12px">
        <thead><tr><th>Medicamento</th><th>Dosis</th><th>Desde</th><th></th></tr></thead>
        <tbody>
        @forelse($paciente->medicamentos as $m)
            <tr>
                <td><strong>{{ $m->nombre_comercial }}</strong> <span class="muted">{{ $m->presentacion }}</span></td>
                <td>{{ $m->pivot->dosis ?: '—' }}</td>
                <td>{{ $m->pivot->fecha_inicio ? \Carbon\Carbon::parse($m->pivot->fecha_inicio)->format('d/m/Y') : '—' }}</td>
                <td style="text-align:right">
                    <form action="{{ route('antecedentes.medicamentos.destroy', [$paciente, $m]) }}" method="POST">@csrf @method('DELETE')<button class="act act-del">Quitar</button></form>
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="muted">Sin medicación habitual registrada.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
