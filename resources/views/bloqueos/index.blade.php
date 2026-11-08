@extends('layouts.app')
@section('title', 'Bloqueos de Agenda')
@push('styles') @include('partials.clinico-styles') @endpush
@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">⛔ Bloqueos de Agenda (P3)</h1>
        <p class="page-subtitle">Feriados, vacaciones y permisos. En esas fechas no se ofrecen fichas para programar o reprogramar citas (CU30).</p>
    </div>
</div>

@if($errors->any())
    <div class="notice notice-err">@foreach($errors->all() as $e) <div>• {{ $e }}</div> @endforeach</div>
@endif

@if(session('afectadas'))
    <div class="notice notice-warn">
        <strong>⚠ Existen {{ count(session('afectadas')) }} cita(s) activa(s) en el período a bloquear.</strong>
        Confirme para guardar el bloqueo; luego podrá reprogramarlas (CU16).
        <table class="data-table" style="margin-top:8px; background:#fff">
            <thead><tr><th>Cita</th><th>Fecha y hora</th><th>Paciente</th><th>Médico</th><th>Estado</th></tr></thead>
            <tbody>
            @foreach(session('afectadas') as $c)
                <tr><td>{{ $c['codigo'] }}</td><td>{{ $c['fecha'] }}</td><td>{{ $c['paciente'] }}</td><td>Dr(a). {{ $c['medico'] }}</td><td>{{ $c['estado'] }}</td></tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endif

<div class="grid-2" style="grid-template-columns: minmax(300px, 1fr) minmax(460px, 2fr)">
    <!-- Nuevo bloqueo -->
    <form action="{{ route('bloqueos.store') }}" method="POST" class="card">
        @csrf
        <div class="card-title">Nuevo bloqueo</div>
        <div class="form-grid" style="grid-template-columns: 1fr 1fr">
            <div class="form-group full"><label class="form-label">Médico</label>
                <select name="medico_id" class="form-control">
                    <option value="">Todos (bloqueo general / feriado)</option>
                    @foreach($medicos as $m)
                        <option value="{{ $m->id }}" {{ (string) old('medico_id') === (string) $m->id ? 'selected' : '' }}>Dr(a). {{ $m->persona->nombre_completo ?? $m->codigo }}</option>
                    @endforeach
                </select></div>
            <div class="form-group full"><label class="form-label">Tipo *</label>
                <select name="tipo" class="form-control" required>
                    @foreach(['Feriado', 'Vacaciones', 'Permiso', 'Otro'] as $t)
                        <option {{ old('tipo') === $t ? 'selected' : '' }}>{{ $t }}</option>
                    @endforeach
                </select></div>
            <div class="form-group"><label class="form-label">Desde *</label><input type="date" name="fecha_inicio" class="form-control" required value="{{ old('fecha_inicio') }}"></div>
            <div class="form-group"><label class="form-label">Hasta *</label><input type="date" name="fecha_fin" class="form-control" required value="{{ old('fecha_fin') }}"></div>
            <div class="form-group"><label class="form-label">Hora inicio (opcional)</label><input type="time" name="hora_inicio" class="form-control" value="{{ old('hora_inicio') }}"></div>
            <div class="form-group"><label class="form-label">Hora fin (opcional)</label><input type="time" name="hora_fin" class="form-control" value="{{ old('hora_fin') }}"></div>
            <div class="form-group full"><label class="form-label">Motivo *</label><input name="motivo" class="form-control" required minlength="3" placeholder="Ej. Navidad, vacaciones anuales, congreso médico" value="{{ old('motivo') }}"></div>
            @if(session('afectadas'))
                <label class="form-group full" style="flex-direction:row; align-items:center; font-size:12.5px; color:#92400e">
                    <input type="checkbox" name="confirmar" value="1" required> Confirmo el bloqueo aunque existan citas en el período
                </label>
            @endif
        </div>
        <p class="muted" style="margin-top:8px">Sin horas, se bloquea el día completo. Con horas, solo las fichas de ese rango.</p>
        <div style="display:flex; justify-content:flex-end; margin-top:12px"><button class="btn btn-primary">Guardar bloqueo</button></div>
    </form>

    <!-- Listado -->
    <div class="card" style="padding:0; overflow-x:auto">
        <form method="GET" action="{{ route('bloqueos.index') }}" style="display:flex; gap:8px; padding:14px 16px; flex-wrap:wrap">
            <select name="ver" class="form-control" style="max-width:180px">
                <option value="vigentes" {{ $ver === 'vigentes' ? 'selected' : '' }}>Vigentes</option>
                <option value="todos" {{ $ver === 'todos' ? 'selected' : '' }}>Todos</option>
            </select>
            <select name="medico_id" class="form-control" style="max-width:240px">
                <option value="">Todos los médicos</option>
                <option value="general" {{ $medicoId === 'general' ? 'selected' : '' }}>Solo generales</option>
                @foreach($medicos as $m)
                    <option value="{{ $m->id }}" {{ (string) $medicoId === (string) $m->id ? 'selected' : '' }}>Dr(a). {{ $m->persona->nombre_completo ?? $m->codigo }}</option>
                @endforeach
            </select>
            <button class="btn btn-light">Filtrar</button>
        </form>
        <table class="data-table">
            <thead><tr><th>Tipo</th><th>Médico</th><th>Período</th><th>Motivo</th><th>Citas</th><th>Estado</th><th style="text-align:right">Acciones</th></tr></thead>
            <tbody>
            @forelse($bloqueos as $b)
                <tr>
                    <td><strong>{{ $b->tipo }}</strong></td>
                    <td>{{ $b->medico ? 'Dr(a). ' . ($b->medico->persona->nombre_completo ?? '') : 'Todos' }}</td>
                    <td>{{ $b->descripcion_periodo }}</td>
                    <td>{{ $b->motivo }}</td>
                    <td>@if(($afectadas[$b->id] ?? 0) > 0)<span class="badge b-moderada">{{ $afectadas[$b->id] }} por reprogramar</span>@else <span class="muted">0</span> @endif</td>
                    <td><span class="badge {{ $b->estado ? 'b-on' : 'b-off' }}">{{ $b->estado ? 'Activo' : 'Inactivo' }}</span></td>
                    <td style="text-align:right; white-space:nowrap">
                        <a href="{{ route('bloqueos.show', $b) }}" class="act act-info">Ver</a>
                        <form action="{{ route('bloqueos.estado', $b) }}" method="POST" style="display:inline">
                            @csrf @method('PATCH')
                            <button class="act {{ $b->estado ? 'act-del' : 'act-ok' }}">{{ $b->estado ? 'Desactivar' : 'Activar' }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" style="text-align:center; color:#64748b; padding:22px">No hay bloqueos con el filtro seleccionado.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div style="padding:10px 16px">{{ $bloqueos->links() }}</div>
    </div>
</div>
@endsection
