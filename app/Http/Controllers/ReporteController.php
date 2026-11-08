<?php

namespace App\Http\Controllers;

use App\Models\Especialidad;
use App\Models\EstadoCita;
use App\Models\Medico;
use App\Services\BitacoraService;
use App\Services\ReporteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controlador de Reportes (Paquete P6 / CU21 Generar reportes, CU22 Exportar reporte)
 */
class ReporteController extends Controller
{
    /**
     * [CU21]: Formulario de parámetros y, si se pidió, el reporte con totales y gráfico
     */
    public function index(Request $request)
    {
        $medicos = Medico::with('persona')->get();
        $especialidades = Especialidad::orderBy('nombre')->get();
        $estados = EstadoCita::orderBy('id')->get();
        $tipos = ReporteService::TIPOS;
        $reporte = null;
        $filtros = [
            'tipo' => $request->input('tipo', 'resumen'),
            'desde' => $request->input('desde', today()->startOfMonth()->toDateString()),
            'hasta' => $request->input('hasta', today()->endOfMonth()->toDateString()),
            'medico_id' => $request->input('medico_id'),
            'especialidad_id' => $request->input('especialidad_id'),
            'estado_id' => $request->input('estado_id'),
        ];

        if ($request->has('generar')) {
            $filtros = $this->validar($request) + $filtros;
            $reporte = ReporteService::generar($filtros);
            BitacoraService::registrar('GENERAR_REPORTE', 'citas', null, "Reporte '{$reporte['titulo']}' {$reporte['subtitulo']}" . $this->descFiltros($filtros));
        }

        return view('reportes.index', compact('medicos', 'especialidades', 'estados', 'tipos', 'reporte', 'filtros'));
    }

    /**
     * [CU22]: Exporta el reporte con los mismos filtros a PDF o CSV (Excel)
     */
    public function exportar(Request $request, string $formato)
    {
        abort_unless(in_array($formato, ['pdf', 'csv'], true), 404);
        $filtros = $this->validar($request);

        try {
            $reporte = ReporteService::generar($filtros);
            $nombre = 'reporte_' . $filtros['tipo'] . '_' . $filtros['desde'] . '_' . $filtros['hasta'];
            BitacoraService::registrar('EXPORTAR_REPORTE', 'citas', null, 'Exportación ' . strtoupper($formato) . " del reporte '{$reporte['titulo']}' {$reporte['subtitulo']}" . $this->descFiltros($filtros));

            if ($formato === 'csv') {
                return $this->csv($reporte, $nombre . '.csv');
            }

            $datos = ['reporte' => $reporte, 'filtros' => $this->descFiltros($filtros), 'generado' => now()];
            // PDF real con dompdf si está instalado; si no, versión imprimible (Guardar como PDF del navegador)
            if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                return \Barryvdh\DomPDF\Facade\Pdf::loadView('reportes.pdf', $datos + ['imprimir' => false])
                    ->setPaper('letter', count($reporte['columnas']) > 5 ? 'landscape' : 'portrait')
                    ->download($nombre . '.pdf');
            }
            return view('reportes.pdf', $datos + ['imprimir' => true]);
        } catch (\Throwable $e) {
            Log::error('Error al exportar reporte: ' . $e->getMessage());
            return redirect()->route('reportes.index')->with('error', 'No se pudo generar el archivo del reporte. Intente nuevamente.');
        }
    }

    // -------------------------------------------------------------------------

    private function validar(Request $request): array
    {
        return $request->validate([
            'tipo' => 'required|in:' . implode(',', array_keys(ReporteService::TIPOS)),
            'desde' => 'required|date',
            'hasta' => 'required|date|after_or_equal:desde',
            'medico_id' => 'nullable|exists:medicos,id',
            'especialidad_id' => 'nullable|exists:especialidades,id',
            'estado_id' => 'nullable|exists:estados_cita,id',
        ], [
            'hasta.after_or_equal' => 'Rango de fechas inválido: la fecha final no puede ser anterior a la inicial.',
        ]);
    }

    private function descFiltros(array $f): string
    {
        $p = [];
        if (!empty($f['medico_id'])) {
            $p[] = 'Médico: Dr(a). ' . (Medico::with('persona')->find($f['medico_id'])->persona->nombre_completo ?? '');
        }
        if (!empty($f['especialidad_id'])) {
            $p[] = 'Especialidad: ' . Especialidad::find($f['especialidad_id'])?->nombre;
        }
        if (!empty($f['estado_id'])) {
            $p[] = 'Estado: ' . EstadoCita::find($f['estado_id'])?->nombre;
        }
        return $p ? ' (' . implode(' · ', $p) . ')' : '';
    }

    /** CSV compatible con Excel: UTF-8 con BOM y separador punto y coma. */
    private function csv(array $r, string $archivo)
    {
        return response()->streamDownload(function () use ($r) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [$r['titulo'] . ' - ' . $r['subtitulo']], ';');
            fputcsv($out, $r['columnas'], ';');
            foreach ($r['filas'] as $fila) {
                fputcsv($out, array_map(fn ($c) => $fila[$c] ?? '', $r['columnas']), ';');
            }
            fputcsv($out, array_map(fn ($c) => $r['totales'][$c] ?? '', $r['columnas']), ';');
            fclose($out);
        }, $archivo, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
