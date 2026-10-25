<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BitacoraController;
use App\Http\Controllers\CitaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EspecialidadController;
use App\Http\Controllers\HorarioAtencionController;
use App\Http\Controllers\MedicoController;
use App\Http\Controllers\PacienteController;
use App\Http\Controllers\UsuarioController;
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

    // Modulo P1: Usuarios, Roles y Bitacora (CU01 - CU05)
    Route::middleware('rol:Administrador')->group(function () {
        // Gestion de Usuarios y Asignacion de Roles (CU03 / CU04)
        Route::resource('usuarios', UsuarioController::class);
        Route::patch('usuarios/{usuario}/toggle-estado', [UsuarioController::class, 'toggleEstado'])->name('usuarios.toggleEstado');

        // Bitacora de Auditoria del Sistema (RN15 / CU05)
        Route::get('bitacora', [BitacoraController::class, 'index'])->name('bitacora.index');
    });

    // Modulo P2: Gestion de Pacientes (CU06 / CU07)
    Route::middleware('rol:Administrador,Recepcionista,Medico')->group(function () {
        Route::resource('pacientes', PacienteController::class);
        Route::patch('pacientes/{paciente}/toggle-estado', [PacienteController::class, 'toggleEstado'])->name('pacientes.toggleEstado');
    });

    // Modulo P3: Gestion Medica y Horarios (CU10 - CU13)
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

    // Modulo P4: Programacion de Citas Medicas - Ciclo 1 (CU14: Disponibilidad / CU15: Programar Cita)
    Route::middleware('rol:Administrador,Recepcionista,Medico,Paciente')->group(function () {
        // API Consulta de Fichas Disponibles en vivo (CU14)
        Route::get('citas/consultar-fichas', [CitaController::class, 'consultarFichas'])->name('citas.consultarFichas');

        // Registro y Comprobante de Citas (CU15)
        Route::resource('citas', CitaController::class)->only(['index', 'create', 'store', 'show']);
    });
});
