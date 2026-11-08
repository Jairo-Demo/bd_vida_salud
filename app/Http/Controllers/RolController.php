<?php

namespace App\Http\Controllers;

use App\Models\Permiso;
use App\Models\Rol;
use App\Services\BitacoraService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Controlador de Roles y Permisos (Paquete P1 / CU04 Asignar rol y permisos)
 * Matriz permisos x roles. Las rutas del Ciclo 2 verifican estos permisos (middleware 'permiso').
 */
class RolController extends Controller
{
    /** Grupos de la matriz según el prefijo del código del permiso. */
    private const GRUPOS = [
        'P1 Acceso y seguridad' => ['USUARIOS', 'BITACORA', 'ROLES'],
        'P2 Pacientes' => ['PACIENTES', 'PERFIL'],
        'P3 Médicos y horarios' => ['ESPECIALIDADES', 'MEDICOS', 'HORARIOS', 'BLOQUEOS'],
        'P4 / P5 Citas y agenda' => ['CITAS', 'AGENDA'],
        'P6 Reportes' => ['REPORTES'],
        'P7 Atención médica' => ['ANTECEDENTES', 'RECETAS', 'CATALOGOS', 'HISTORIA'],
        'P8 Opcionales' => ['COMANDO', 'RECORDATORIOS'],
    ];

    public function index()
    {
        $roles = Rol::with('permisos')->orderBy('id')->get();
        $permisos = Permiso::orderBy('codigo')->get();

        $grupos = [];
        foreach (self::GRUPOS as $nombre => $prefijos) {
            $grupos[$nombre] = $permisos->filter(fn ($p) => in_array(explode('_', $p->codigo)[0], $prefijos, true))->values();
        }
        $usados = collect($grupos)->flatten()->pluck('id');
        $otros = $permisos->whereNotIn('id', $usados)->values();
        if ($otros->isNotEmpty()) {
            $grupos['Otros'] = $otros;
        }
        $asignados = $roles->mapWithKeys(fn ($r) => [$r->id => $r->permisos->pluck('id')->all()]);

        return view('roles.index', compact('roles', 'grupos', 'asignados'));
    }

    /**
     * Guarda la matriz. El Administrador conserva siempre todos los permisos para no perder el acceso.
     */
    public function update(Request $request)
    {
        $request->validate(['permisos' => 'nullable|array', 'permisos.*' => 'array', 'permisos.*.*' => 'integer|exists:permisos,id']);
        $matriz = $request->input('permisos', []);
        $cambios = [];

        DB::transaction(function () use ($matriz, &$cambios) {
            foreach (Rol::with('permisos')->get() as $rol) {
                $nuevos = $rol->nombre === 'Administrador'
                    ? Permiso::pluck('id')->all()
                    : array_map('intval', $matriz[$rol->id] ?? []);
                $antes = $rol->permisos->pluck('id')->all();
                $agregados = count(array_diff($nuevos, $antes));
                $quitados = count(array_diff($antes, $nuevos));
                if ($agregados || $quitados) {
                    $rol->permisos()->sync($nuevos);
                    $cambios[] = "{$rol->nombre}: +{$agregados} / -{$quitados}";
                }
            }
        });

        if (!$cambios) {
            return back()->with('success', 'No hubo cambios en los permisos.');
        }
        BitacoraService::registrar('ASIGNAR_PERMISOS', 'rol_permiso', null, 'Matriz de permisos actualizada. ' . implode('; ', $cambios));

        return back()->with('success', 'Permisos actualizados: ' . implode('; ', $cambios) . '.');
    }
}
