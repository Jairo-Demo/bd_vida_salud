<?php

namespace App\Http\Controllers;

use App\Models\Recordatorio;
use App\Services\BitacoraService;
use App\Services\RecordatorioService;
use Illuminate\Http\Request;

/**
 * Monitoreo de recordatorios (Paquete P8 / CU31). El envío lo hace la tarea programada
 * recordatorios:enviar; desde aquí el administrador puede forzar el envío o reintentar uno fallido.
 */
class RecordatorioController extends Controller
{
    public function index(Request $request)
    {
        $estado = $request->input('estado');
        $tipo = $request->input('tipo');

        $recordatorios = Recordatorio::with(['cita.paciente.persona'])
            ->when($estado, fn ($q, $v) => $q->where('estado', $v))
            ->when($tipo, fn ($q, $v) => $q->where('tipo', $v))
            ->orderByDesc('fecha_programada')->paginate(20)->withQueryString();

        $conteo = Recordatorio::selectRaw('estado, count(*) as total')->groupBy('estado')->pluck('total', 'estado');
        $vencidos = Recordatorio::where('estado', 'Pendiente')->where('fecha_programada', '<=', now())->count();

        return view('recordatorios.index', compact('recordatorios', 'conteo', 'vencidos', 'estado', 'tipo'));
    }

    /** Ejecuta ahora la misma lógica de la tarea programada. */
    public function enviar()
    {
        $r = RecordatorioService::enviarPendientes();
        BitacoraService::registrar('ENVIO_MANUAL_RECORDATORIOS', 'recordatorios', null, "Envío manual: {$r['enviados']} enviados, {$r['fallidos']} fallidos, {$r['omitidos']} omitidos.");

        return back()->with('success', "Recordatorios enviados: {$r['enviados']} · fallidos: {$r['fallidos']} · omitidos: {$r['omitidos']}.");
    }

    /** Reintenta un recordatorio fallido (CU31 - excepción). */
    public function reintentar(Recordatorio $recordatorio)
    {
        if ($recordatorio->estado !== 'Fallido' || str_starts_with((string) $recordatorio->mensaje, 'Omitido:')) {
            return back()->with('error', 'Solo se reintentan recordatorios fallidos por error de envío.');
        }
        $r = RecordatorioService::enviarPendientes(true, $recordatorio->id);

        return back()->with($r['enviados'] ? 'success' : 'error', $r['enviados'] ? 'Recordatorio reenviado.' : 'El envío volvió a fallar: revise el correo del paciente o la configuración de correo.');
    }
}
