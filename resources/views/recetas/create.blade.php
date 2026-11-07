@extends('layouts.app')
@section('title', 'Emitir Receta')
@push('styles')
@include('partials.clinico-styles')
<style>
    .item-row { border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 12px; margin-bottom: 10px; background: #fff; }
    .item-row.alerta { border-color: #f59e0b; background: #fffbeb; }
    .item-row.bloqueo { border-color: #dc2626; background: #fef2f2; }
    .item-alert { font-size: 12.5px; margin-top: 8px; font-weight: 600; }
</style>
@endpush
@section('content')
@php $p = $consulta->cita->paciente; @endphp
<div class="page-header">
    <div>
        <h1 class="page-title">💊 Emitir Receta — {{ $p->nombre_completo }}</h1>
        <p class="page-subtitle">Cita {{ $consulta->cita->codigo }} · Diagnóstico: {{ \Illuminate\Support\Str::limit($consulta->diagnostico, 80) }} (CU25)</p>
    </div>
    <a href="{{ route('consultas.show', $consulta) }}" class="btn btn-light">&larr; Volver a la consulta</a>
</div>

@if($p->alergias->isNotEmpty())
    <div class="notice notice-warn">⚠ <strong>Alergias registradas:</strong>
        @foreach($p->alergias as $a) {{ $a->nombre }} ({{ $a->pivot->severidad }}){{ !$loop->last ? ',' : '' }} @endforeach
        — el sistema verifica cada medicamento (CU26).</div>
@else
    <div class="notice notice-info">El paciente no tiene alergias registradas: la verificación se completa sin alertas.</div>
@endif

@if($errors->any())
    <div class="notice notice-err">@foreach($errors->all() as $e) <div>• {{ $e }}</div> @endforeach</div>
@endif

<form action="{{ route('recetas.store') }}" method="POST" class="card" id="formReceta">
    @csrf
    <input type="hidden" name="consulta_id" value="{{ $consulta->id }}">
    <div class="card-title">Medicamentos</div>
    <div id="items"></div>
    <button type="button" class="btn btn-light btn-sm" onclick="agregarItem()">+ Agregar medicamento</button>

    <div class="form-group full" style="margin-top:16px">
        <label class="form-label" for="observaciones">Observaciones / justificación</label>
        <textarea name="observaciones" id="observaciones" rows="2" class="form-control" placeholder="Indicaciones generales. Si confirma un medicamento con alerta de alergia, justifique aquí.">{{ old('observaciones') }}</textarea>
    </div>
    <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:16px">
        <a href="{{ route('consultas.show', $consulta) }}" class="btn btn-light">Cancelar</a>
        <button type="submit" class="btn btn-primary">✓ Guardar receta</button>
    </div>
</form>

<template id="tplItem">
    <div class="item-row">
        <div class="form-grid">
            <div class="form-group" style="grid-column: span 2"><label class="form-label">Medicamento *</label>
                <select class="form-control f-med" required onchange="verificar(this)">
                    <option value="">Seleccione...</option>
                    @foreach($medicamentos as $m)<option value="{{ $m->id }}">{{ $m->nombre_comercial }} — {{ $m->presentacion }} ({{ $m->principio_activo }})</option>@endforeach
                </select></div>
            <div class="form-group"><label class="form-label">Dosis *</label><input class="form-control f-dosis" required placeholder="1 comprimido"></div>
            <div class="form-group"><label class="form-label">Frecuencia *</label><input class="form-control f-frec" required placeholder="Cada 8 horas"></div>
            <div class="form-group"><label class="form-label">Duración</label><input class="form-control f-dur" placeholder="5 días"></div>
            <div class="form-group"><label class="form-label">Cantidad *</label><input type="number" min="1" class="form-control f-cant" required value="1"></div>
            <div class="form-group full"><label class="form-label">Indicaciones</label><input class="form-control f-ind" placeholder="Después de las comidas"></div>
        </div>
        <div class="item-alert" style="display:none"></div>
        <label class="f-conf-wrap" style="display:none; font-size:12.5px; margin-top:6px"><input type="checkbox" class="f-conf" value="1"> Confirmo recetarlo bajo mi responsabilidad (justificar en observaciones)</label>
        <div style="text-align:right"><button type="button" class="act act-del" onclick="this.closest('.item-row').remove(); renumerar()">Quitar</button></div>
    </div>
</template>

@push('scripts')
<script>
    const viejos = @json(old('items', []));
    const urlVerificar = "{{ route('recetas.verificarAlergia') }}";

    function agregarItem(datos = {}) {
        const nodo = document.getElementById('tplItem').content.firstElementChild.cloneNode(true);
        document.getElementById('items').appendChild(nodo);
        nodo.querySelector('.f-med').value = datos.medicamento_id || '';
        nodo.querySelector('.f-dosis').value = datos.dosis || '';
        nodo.querySelector('.f-frec').value = datos.frecuencia || '';
        nodo.querySelector('.f-dur').value = datos.duracion_tratamiento || '';
        nodo.querySelector('.f-cant').value = datos.cantidad || 1;
        nodo.querySelector('.f-ind').value = datos.indicaciones || '';
        nodo.querySelector('.f-conf').checked = !!datos.confirmar_alergia;
        renumerar();
        if (datos.medicamento_id) verificar(nodo.querySelector('.f-med'));
    }

    function renumerar() {
        document.querySelectorAll('#items .item-row').forEach((row, i) => {
            const map = {'.f-med': 'medicamento_id', '.f-dosis': 'dosis', '.f-frec': 'frecuencia', '.f-dur': 'duracion_tratamiento', '.f-cant': 'cantidad', '.f-ind': 'indicaciones', '.f-conf': 'confirmar_alergia'};
            for (const [sel, campo] of Object.entries(map)) row.querySelector(sel).name = `items[${i}][${campo}]`;
        });
    }

    // CU26: verificación de alergias en vivo al elegir el medicamento
    async function verificar(select) {
        const row = select.closest('.item-row');
        const box = row.querySelector('.item-alert'), conf = row.querySelector('.f-conf-wrap');
        row.classList.remove('alerta', 'bloqueo'); box.style.display = 'none'; conf.style.display = 'none';
        if (!select.value) return;
        const data = await (await fetch(`${urlVerificar}?consulta={{ $consulta->id }}&medicamento=${select.value}`)).json();
        if (!data.alertas || data.alertas.length === 0) return;
        const grave = data.alertas.some(a => a.bloquea);
        box.innerHTML = data.alertas.map(a => `⚠ Alerta de alergia: el paciente es alérgico a <b>${a.alergia}</b> (${a.severidad}${a.reaccion ? ' - ' + a.reaccion : ''}).`).join('<br>')
            + (grave ? '<br>⛔ Alergia grave: este medicamento está bloqueado, reemplácelo.' : '');
        box.style.color = grave ? '#991b1b' : '#92400e';
        box.style.display = 'block';
        row.classList.add(grave ? 'bloqueo' : 'alerta');
        if (!grave) conf.style.display = 'block';
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (Array.isArray(viejos) && viejos.length) viejos.forEach(agregarItem);
        else if (viejos && typeof viejos === 'object' && Object.keys(viejos).length) Object.values(viejos).forEach(agregarItem);
        else agregarItem();
    });
</script>
@endpush
@endsection
