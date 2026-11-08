<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Construcción de reportes (Paquete P6 / CU21 Generar reportes, CU22 Exportar reporte).
 * Todas las consultas son agregadas (GROUP BY) y parametrizadas con el Query Builder.
 * Cada reporte devuelve: titulo, columnas, filas, totales y grafico (etiqueta => valor).
 */
class ReporteService
{
    public const TIPOS = [
        'resumen' => 'Resumen de atendidas, canceladas e inasistencias',
        'estado' => 'Citas por estado',
        'fecha' => 'Citas por fecha',
        'medico' => 'Citas por médico',
        'especialidad' => 'Citas por especialidad',
        'agenda' => 'Agenda (detalle de citas)',
        'pacientes' => 'Pacientes registrados',
    ];

    private const ESTADOS = ['Programada', 'Confirmada', 'Atendida', 'Cancelada', 'Inasistencia'];

    public static function generar(array $f): array
    {
        $metodo = 'reporte' . ucfirst($f['tipo']);
        $r = self::$metodo($f);
        $r['tipo'] = $f['tipo'];
        $r['subtitulo'] = 'Del ' . Carbon::parse($f['desde'])->format('d/m/Y') . ' al ' . Carbon::parse($f['hasta'])->format('d/m/Y');
        return $r;
    }

    /** Citas del período con los filtros opcionales de médico, especialidad y estado. */
    private static function citas(array $f)
    {
        return DB::table('citas')
            ->join('estados_cita', 'estados_cita.id', '=', 'citas.estado_id')
            ->whereBetween('citas.fecha', [$f['desde'], $f['hasta']])
            ->when($f['medico_id'] ?? null, fn ($q, $v) => $q->where('citas.medico_id', $v))
            ->when($f['especialidad_id'] ?? null, fn ($q, $v) => $q->where('citas.especialidad_id', $v))
            ->when($f['estado_id'] ?? null, fn ($q, $v) => $q->where('citas.estado_id', $v));
    }

    /** Columnas SUM(CASE...) por cada estado (los nombres son constantes, no datos del usuario). */
    private static function columnasEstado(): string
    {
        return collect(self::ESTADOS)->map(fn ($e) => "SUM(CASE WHEN estados_cita.nombre = '{$e}' THEN 1 ELSE 0 END) AS \"{$e}\"")->implode(', ');
    }

    private static function totalesDe(array $filas, array $numericas): array
    {
        $t = [];
        foreach ($numericas as $c) {
            $t[$c] = array_sum(array_map(fn ($f) => (float) ($f[$c] ?? 0), $filas));
        }
        return $t;
    }

    private static function porcentaje($parte, $total): string
    {
        return $total > 0 ? number_format($parte * 100 / $total, 1) . ' %' : '0.0 %';
    }

    private static function porEstado($r): array
    {
        return collect(self::ESTADOS)->mapWithKeys(fn ($e) => [$e => (int) $r->{$e}])->all();
    }

    // ------------------------------------------------------------------------

    private static function reporteResumen(array $f): array
    {
        $porEstado = self::citas($f)->selectRaw('estados_cita.nombre AS estado, COUNT(*) AS total')
            ->groupBy('estados_cita.nombre')->pluck('total', 'estado');
        $total = $porEstado->sum();
        $finalizadas = ($porEstado['Atendida'] ?? 0) + ($porEstado['Cancelada'] ?? 0) + ($porEstado['Inasistencia'] ?? 0);

        $filas = [];
        foreach (['Atendida', 'Cancelada', 'Inasistencia', 'Programada', 'Confirmada'] as $e) {
            $filas[] = ['Indicador' => $e, 'Cantidad' => (int) ($porEstado[$e] ?? 0), '% del total' => self::porcentaje($porEstado[$e] ?? 0, $total)];
        }
        $filas[] = ['Indicador' => 'Tasa de asistencia (atendidas / finalizadas)', 'Cantidad' => (int) ($porEstado['Atendida'] ?? 0), '% del total' => self::porcentaje($porEstado['Atendida'] ?? 0, $finalizadas)];

        return [
            'titulo' => 'Resumen de atendidas, canceladas e inasistencias',
            'columnas' => ['Indicador', 'Cantidad', '% del total'],
            'filas' => $filas,
            'totales' => ['Indicador' => 'Total de citas', 'Cantidad' => $total, '% del total' => $total ? '100.0 %' : '0.0 %'],
            'grafico' => collect(self::ESTADOS)->mapWithKeys(fn ($e) => [$e => (int) ($porEstado[$e] ?? 0)])->all(),
        ];
    }

    private static function reporteEstado(array $f): array
    {
        $rows = self::citas($f)->selectRaw('estados_cita.id, estados_cita.nombre AS estado, COUNT(*) AS total')
            ->groupBy('estados_cita.id', 'estados_cita.nombre')->orderBy('estados_cita.id')->get();
        $total = $rows->sum('total');
        $filas = $rows->map(fn ($r) => ['Estado' => $r->estado, 'Citas' => (int) $r->total, '%' => self::porcentaje($r->total, $total)])->all();

        return [
            'titulo' => 'Citas por estado',
            'columnas' => ['Estado', 'Citas', '%'],
            'filas' => $filas,
            'totales' => ['Estado' => 'Total', 'Citas' => $total, '%' => $total ? '100.0 %' : '0.0 %'],
            'grafico' => $rows->pluck('total', 'estado')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    private static function reporteFecha(array $f): array
    {
        $rows = self::citas($f)->selectRaw('citas.fecha, COUNT(*) AS total, ' . self::columnasEstado())
            ->groupBy('citas.fecha')->orderBy('citas.fecha')->get();
        $filas = $rows->map(fn ($r) => ['Fecha' => Carbon::parse($r->fecha)->format('d/m/Y'), 'Total' => (int) $r->total] + self::porEstado($r))->all();

        return [
            'titulo' => 'Citas por fecha',
            'columnas' => array_merge(['Fecha', 'Total'], self::ESTADOS),
            'filas' => $filas,
            'totales' => ['Fecha' => 'Total'] + self::totalesDe($filas, array_merge(['Total'], self::ESTADOS)),
            'grafico' => collect($filas)->pluck('Total', 'Fecha')->all(),
        ];
    }

    private static function reporteMedico(array $f): array
    {
        $rows = self::citas($f)
            ->join('medicos', 'medicos.id', '=', 'citas.medico_id')
            ->join('personas', 'personas.id', '=', 'medicos.persona_id')
            ->selectRaw("medicos.id, CONCAT(personas.nombres, ' ', personas.apellidos) AS medico, COUNT(*) AS total, " . self::columnasEstado())
            ->groupBy('medicos.id', 'personas.nombres', 'personas.apellidos')->orderByDesc('total')->get();

        $filas = $rows->map(function ($r) {
            $fin = $r->Atendida + $r->Cancelada + $r->Inasistencia;
            return ['Médico' => 'Dr(a). ' . $r->medico, 'Total' => (int) $r->total] + self::porEstado($r)
                + ['% asistencia' => self::porcentaje($r->Atendida, $fin)];
        })->all();

        return [
            'titulo' => 'Citas por médico',
            'columnas' => array_merge(['Médico', 'Total'], self::ESTADOS, ['% asistencia']),
            'filas' => $filas,
            'totales' => ['Médico' => 'Total'] + self::totalesDe($filas, array_merge(['Total'], self::ESTADOS)) + ['% asistencia' => ''],
            'grafico' => collect($filas)->pluck('Total', 'Médico')->all(),
        ];
    }

    private static function reporteEspecialidad(array $f): array
    {
        $rows = self::citas($f)
            ->join('especialidades', 'especialidades.id', '=', 'citas.especialidad_id')
            ->selectRaw('especialidades.nombre AS especialidad, COUNT(*) AS total, ' . self::columnasEstado())
            ->groupBy('especialidades.nombre')->orderByDesc('total')->get();
        $filas = $rows->map(fn ($r) => ['Especialidad' => $r->especialidad, 'Total' => (int) $r->total] + self::porEstado($r))->all();

        return [
            'titulo' => 'Citas por especialidad',
            'columnas' => array_merge(['Especialidad', 'Total'], self::ESTADOS),
            'filas' => $filas,
            'totales' => ['Especialidad' => 'Total'] + self::totalesDe($filas, array_merge(['Total'], self::ESTADOS)),
            'grafico' => collect($filas)->pluck('Total', 'Especialidad')->all(),
        ];
    }

    private static function reporteAgenda(array $f): array
    {
        $rows = self::citas($f)
            ->join('pacientes', 'pacientes.id', '=', 'citas.paciente_id')
            ->join('personas AS pp', 'pp.id', '=', 'pacientes.persona_id')
            ->join('medicos', 'medicos.id', '=', 'citas.medico_id')
            ->join('personas AS pm', 'pm.id', '=', 'medicos.persona_id')
            ->join('especialidades', 'especialidades.id', '=', 'citas.especialidad_id')
            ->selectRaw("citas.fecha, citas.hora_inicio, citas.codigo, CONCAT(pp.nombres, ' ', pp.apellidos) AS paciente,
                         CONCAT(pm.nombres, ' ', pm.apellidos) AS medico, especialidades.nombre AS especialidad, estados_cita.nombre AS estado")
            ->orderBy('citas.fecha')->orderBy('citas.hora_inicio')->limit(1000)->get();

        $filas = $rows->map(fn ($r) => [
            'Fecha' => Carbon::parse($r->fecha)->format('d/m/Y'), 'Hora' => substr($r->hora_inicio, 0, 5), 'Código' => $r->codigo,
            'Paciente' => $r->paciente, 'Médico' => 'Dr(a). ' . $r->medico, 'Especialidad' => $r->especialidad, 'Estado' => $r->estado,
        ])->all();

        return [
            'titulo' => 'Agenda (detalle de citas)',
            'columnas' => ['Fecha', 'Hora', 'Código', 'Paciente', 'Médico', 'Especialidad', 'Estado'],
            'filas' => $filas,
            'totales' => ['Fecha' => 'Total de citas', 'Hora' => count($filas)],
            'grafico' => collect($filas)->countBy('Estado')->all(),
        ];
    }

    private static function reportePacientes(array $f): array
    {
        $rows = DB::table('pacientes')
            ->join('personas', 'personas.id', '=', 'pacientes.persona_id')
            ->leftJoin('citas', 'citas.paciente_id', '=', 'pacientes.id')
            ->whereBetween(DB::raw('CAST(pacientes.created_at AS DATE)'), [$f['desde'], $f['hasta']])
            ->selectRaw("pacientes.codigo, personas.ci, CONCAT(personas.nombres, ' ', personas.apellidos) AS paciente, pacientes.sexo,
                         pacientes.fecha_nacimiento, CAST(pacientes.created_at AS DATE) AS registro, COUNT(citas.id) AS citas")
            ->groupBy('pacientes.id', 'pacientes.codigo', 'personas.ci', 'personas.nombres', 'personas.apellidos', 'pacientes.sexo', 'pacientes.fecha_nacimiento', 'pacientes.created_at')
            ->orderBy('pacientes.created_at')->get();

        $filas = $rows->map(fn ($r) => [
            'Código' => $r->codigo, 'CI' => $r->ci, 'Paciente' => $r->paciente, 'Sexo' => $r->sexo,
            'Edad' => $r->fecha_nacimiento ? Carbon::parse($r->fecha_nacimiento)->age : '', 'Registro' => Carbon::parse($r->registro)->format('d/m/Y'),
            'Citas' => (int) $r->citas,
        ])->all();

        return [
            'titulo' => 'Pacientes registrados',
            'columnas' => ['Código', 'CI', 'Paciente', 'Sexo', 'Edad', 'Registro', 'Citas'],
            'filas' => $filas,
            'totales' => ['Código' => 'Total de pacientes', 'CI' => count($filas), 'Citas' => array_sum(array_column($filas, 'Citas'))],
            'grafico' => collect($filas)->countBy(fn ($r) => $r['Sexo'] === 'F' ? 'Femenino' : ($r['Sexo'] === 'M' ? 'Masculino' : 'Otro'))->all(),
        ];
    }
}
