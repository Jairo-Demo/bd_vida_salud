<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\EstadoCita;
use App\Models\HistorialEstadoCita;
use App\Services\BitacoraService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Controlador de Estados de la Cita (Paquete P5 - Agenda y Asistencia)
 * CU18 Confirmar cita y CU19 Registrar inasistencia.
 * La validez de cada transición también la controla el trigger trg_validar_transicion_estado (RN18).
 */
class CitaEstadoController extends Controller
{
    /**
     * [CU18]: Programada -> Confirmada (fecha actual o futura)
     */
    public function confirmar(Request $request, Cita $cita)
    {
        $request->validate(['observacion' => 'nullable|string|max:255']);

        return $this->transicion($cita, 'Confirmada', function (Cita $c) {
            if ($c->estadoCita->nombre !== 'Programada') {
                return "Solo se confirman citas en estado Programada (la cita está {$c->estadoCita->nombre}).";
            }
            if ($c->fecha->lt(today())) {
                return 'No se puede confirmar una cita de una fecha pasada.';
            }
            return null;
        }, trim($request->input('observacion')) ?: 'Asistencia confirmada por recepción.', 'CONFIRMAR_CITA');
    }

    /**
     * [CU19]: Programada/Confirmada -> Inasistencia (la hora de inicio ya pasó)
     */
    public function inasistencia(Request $request, Cita $cita)
    {
        $request->validate(['observacion' => 'nullable|string|max:255']);

        return $this->transicion($cita, 'Inasistencia', fn (Cita $c) => $this->validarInasistencia($c),
            trim($request->input('observacion')) ?: 'El paciente no se presentó a la cita.', 'REGISTRAR_INASISTENCIA');
    }

    /**
     * [CU19 - Flujo alterno A]: al cierre de la jornada marca en bloque las citas vencidas no atendidas
     */
    public function inasistenciaMasiva(Request $request)
    {
        $request->validate([
            'medico_id' => 'required|exists:medicos,id',
            'fecha' => 'required|date|before_or_equal:today',
        ]);

        $pendientes = EstadoCita::whereIn('nombre', ['Programada', 'Confirmada'])->pluck('id');
        $citas = Cita::with('estadoCita')
            ->where('medico_id', $request->input('medico_id'))
            ->whereDate('fecha', $request->input('fecha'))
            ->whereIn('estado_id', $pendientes)
            ->get()
            ->filter(fn (Cita $c) => $this->validarInasistencia($c) === null);

        $marcadas = 0;
        foreach ($citas as $cita) {
            $r = $this->aplicar($cita, 'Inasistencia', 'Inasistencia registrada al cierre de la jornada.', 'REGISTRAR_INASISTENCIA');
            if ($r === null) {
                $marcadas++;
            }
        }

        return back()->with('success', $marcadas > 0
            ? "Se registró la inasistencia de {$marcadas} cita(s) vencida(s) no atendida(s)."
            : 'No hay citas vencidas pendientes para marcar como inasistencia.');
    }

    // -------------------------------------------------------------------------

    private function validarInasistencia(Cita $c): ?string
    {
        if (!in_array($c->estadoCita->nombre, ['Programada', 'Confirmada'], true)) {
            return "La cita ya está {$c->estadoCita->nombre}: no se puede registrar inasistencia.";
        }
        $inicio = Carbon::parse($c->fecha->toDateString() . ' ' . $c->hora_inicio);
        if ($inicio->isFuture()) {
            return 'Aún no es la hora de la cita: no se puede registrar la inasistencia.';
        }
        return null;
    }

    /** Valida y aplica la transición, devolviendo a la pantalla anterior con el resultado. */
    private function transicion(Cita $cita, string $nuevo, callable $validar, string $motivo, string $accion)
    {
        $cita->loadMissing('estadoCita');
        if ($error = $validar($cita)) {
            return back()->with('error', "Cita {$cita->codigo}: {$error}");
        }
        if ($error = $this->aplicar($cita, $nuevo, $motivo, $accion)) {
            return back()->with('error', "Cita {$cita->codigo}: {$error}");
        }
        return back()->with('success', "Cita {$cita->codigo} marcada como {$nuevo}.");
    }

    /** Cambia el estado dentro de una transacción con historial y bitácora. Devuelve null si tuvo éxito. */
    private function aplicar(Cita $cita, string $nuevo, string $motivo, string $accion): ?string
    {
        try {
            DB::transaction(function () use ($cita, $nuevo, $motivo, $accion) {
                $c = Cita::with('estadoCita')->lockForUpdate()->findOrFail($cita->id);
                $anterior = $c->estado_id;
                $idNuevo = EstadoCita::where('nombre', $nuevo)->value('id');

                $c->update(['estado_id' => $idNuevo]);

                HistorialEstadoCita::create([
                    'cita_id' => $c->id,
                    'estado_anterior_id' => $anterior,
                    'estado_nuevo_id' => $idNuevo,
                    'usuario_id' => Auth::id(),
                    'fecha_hora' => now(),
                    'motivo' => $motivo,
                ]);

                BitacoraService::registrar(
                    accion: $accion,
                    tabla: 'citas',
                    registroId: $c->id,
                    detalles: "Cita {$c->codigo}: {$c->estadoCita->nombre} -> {$nuevo}. {$motivo}"
                );
            });
        } catch (QueryException $e) {
            $msg = $e->getPrevious()?->getMessage() ?? $e->getMessage();
            return preg_match('/ERROR:\s+(.*?)(\n|CONTEXT|\(SQL|$)/s', $msg, $m) ? trim($m[1]) : 'transición no válida.';
        }
        return null;
    }
}
