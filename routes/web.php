<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EspecialidadController;
use App\Http\Controllers\HorarioAtencionController;
use App\Http\Controllers\MedicoController;
use App\Http\Controllers\PacienteController;
use Illuminate\Support\Facades\Route;

// Redireccion inicial al login
Route::get('/', function () {
    return redirect()->route('login');
});

// Rutas publicas de invitados (Guest)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

// Rutas protegidas por autenticacion (Auth)
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Modulo P2: Gestion de Pacientes (RF02 / CU06 - CU09)
    Route::middleware('rol:Administrador,Recepcionista,Medico')->group(function () {
        Route::resource('pacientes', PacienteController::class);
        Route::patch('pacientes/{paciente}/toggle-estado', [PacienteController::class, 'toggleEstado'])->name('pacientes.toggleEstado');
    });

    // Modulo P3: Gestion Medica y Horarios (RF03, RF04, RF05 / CU10 - CU13)
    Route::middleware('rol:Administrador,Recepcionista,Medico')->group(function () {
        // Especialidades Medicas (CU10)
        Route::resource('especialidades', EspecialidadController::class);
        Route::patch('especialidades/{especialidad}/toggle-estado', [EspecialidadController::class, 'toggleEstado'])->name('especialidades.toggleEstado');

        // Plantel Medico y Especialidades (CU11, CU12)
        Route::resource('medicos', MedicoController::class);
        Route::patch('medicos/{medico}/toggle-estado', [MedicoController::class, 'toggleEstado'])->name('medicos.toggleEstado');

        // Horarios de Atencion y Fichas (CU13)
        Route::resource('horarios', HorarioAtencionController::class);
        Route::patch('horarios/{horario}/toggle-estado', [HorarioAtencionController::class, 'toggleEstado'])->name('horarios.toggleEstado');
    });
});
