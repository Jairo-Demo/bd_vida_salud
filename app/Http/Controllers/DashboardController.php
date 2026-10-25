<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\Medico;
use App\Models\Paciente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Muestra el panel principal segun el perfil del usuario autenticado
     */
    public function index()
    {
        $user = Auth::user();
        $rol = $user->rol->nombre ?? 'Usuario';

        // Metricas generales para la pantalla principal
        $totalPacientes = Paciente::where('estado', true)->count();
        $totalMedicos = Medico::where('estado', true)->count();
        $citasHoy = Cita::whereDate('fecha', today())->count();

        return view('dashboard', compact('user', 'rol', 'totalPacientes', 'totalMedicos', 'citasHoy'));
    }
}
