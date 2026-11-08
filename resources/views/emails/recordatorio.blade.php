@php
    $r = $recordatorio; $c = $r->cita;
    $titulo = match ($r->tipo) { 'Reprogramacion' => 'Su cita fue reprogramada', 'Cancelacion' => 'Su cita fue cancelada', default => 'Recordatorio de su cita médica' };
@endphp
<!DOCTYPE html>
<html lang="es">
<body style="margin:0; padding:24px; background:#f1f5f9; font-family: Arial, sans-serif; color:#1e293b;">
<table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px; margin:0 auto; background:#ffffff; border-radius:12px; overflow:hidden;">
    <tr><td style="background:#0284c7; color:#ffffff; padding:18px 24px; font-size:18px; font-weight:bold;">Clínica Vida Salud · {{ $titulo }}</td></tr>
    <tr><td style="padding:22px 24px; font-size:14px; line-height:1.6;">
        Estimado(a) <strong>{{ $c->paciente->nombre_completo }}</strong>:<br><br>
        {{ $r->mensaje }}<br><br>
        <table cellpadding="6" style="font-size:14px; border-collapse:collapse;">
            <tr><td style="color:#64748b">Cita</td><td><strong>{{ $c->codigo }}</strong></td></tr>
            <tr><td style="color:#64748b">Fecha</td><td><strong>{{ $c->fecha->format('d/m/Y') }}</strong> a las <strong>{{ substr($c->hora_inicio, 0, 5) }}</strong></td></tr>
            <tr><td style="color:#64748b">Médico</td><td>Dr(a). {{ $c->medico->persona->nombre_completo ?? '' }} · {{ $c->especialidad->nombre ?? '' }}</td></tr>
            <tr><td style="color:#64748b">Ficha</td><td>#{{ $c->ficha->numero ?? '—' }} · {{ $c->modalidad }}</td></tr>
            <tr><td style="color:#64748b">Estado</td><td>{{ $c->estadoCita->nombre ?? '' }}</td></tr>
        </table>
        <br>
        @if($r->tipo !== 'Cancelacion')
            Por favor llegue 10 minutos antes con su carnet de identidad. Si no puede asistir, comuníquese con la clínica para reprogramar o cancelar.
        @else
            Puede agendar una nueva cita en la clínica o desde el sistema web.
        @endif
        <br><br>Atentamente,<br>Clínica Vida Salud
    </td></tr>
    <tr><td style="padding:12px 24px; font-size:11px; color:#94a3b8; background:#f8fafc;">Mensaje automático del Sistema Web de Gestión de Citas Médicas. No responda a este correo.</td></tr>
</table>
</body>
</html>
