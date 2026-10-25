<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Controlador de Auditoría y Bitácora del Sistema (RN15 / CU05)
 * Visualización inmutable del historial de eventos y operaciones.
 */
class BitacoraController extends Controller
{
    /**
     * [READ - Consulta]: Listado cronológico de eventos registrados con filtros
     */
    public function index(Request $request)
    {
        $buscar = trim($request->input('buscar'));
        $usuarioId = $request->input('usuario_id');
        $accion = $request->input('accion');
        $fechaDesde = $request->input('fecha_desde');
        $fechaHasta = $request->input('fecha_hasta');

        $query = Bitacora::with('usuario')->orderBy('fecha_hora', 'desc');

        if (!empty($buscar)) {
            $query->where(function ($q) use ($buscar) {
                $q->where('detalles', 'ilike', "%{$buscar}%")
                  ->orWhere('ip', 'ilike', "%{$buscar}%")
                  ->orWhere('tabla_afectada', 'ilike', "%{$buscar}%");
            });
        }

        if (!empty($usuarioId)) {
            $query->where('usuario_id', $usuarioId);
        }

        if (!empty($accion)) {
            $query->where('accion', $accion);
        }

        if (!empty($fechaDesde)) {
            $query->whereDate('fecha_hora', '>=', $fechaDesde);
        }

        if (!empty($fechaHasta)) {
            $query->whereDate('fecha_hora', '<=', $fechaHasta);
        }

        $bitacoras = $query->paginate(20)->withQueryString();
        $usuarios = User::orderBy('name')->get();
        $acciones = Bitacora::select('accion')->distinct()->pluck('accion');

        return view('bitacora.index', compact('bitacoras', 'usuarios', 'acciones', 'buscar', 'usuarioId', 'accion', 'fechaDesde', 'fechaHasta'));
    }
}
