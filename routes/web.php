<?php

use App\Http\Controllers\AgendaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BitacoraController;
use App\Http\Controllers\CitaController;
use App\Http\Controllers\CitaEstadoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EspecialidadController;
use App\Http\Controllers\HorarioAtencionController;
use App\Http\Controllers\MedicoController;
use App\Http\Controllers\PacienteController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\AntecedenteAlergiaController;
use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\ConsultaController;
use App\Http\Controllers\HistoriaClinicaController;
use App\Http\Controllers\RecetaController;
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

    // Modulo P4: Programacion de Citas Medicas (CU14 Disponibilidad, CU15 Programar, CU17 Cancelar)
    Route::middleware('rol:Administrador,Recepcionista,Medico,Paciente')->group(function () {
        // API Consulta de Fichas Disponibles en vivo (CU14)
        Route::get('citas/consultar-fichas', [CitaController::class, 'consultarFichas'])->name('citas.consultarFichas');

        // Registro y Comprobante de Citas (CU15)
        Route::resource('citas', CitaController::class)->only(['index', 'create', 'store', 'show']);

        // Ciclo 2 - Cancelar cita (CU17): Recepcionista y Paciente (solo sus citas, validado en el controlador)
        Route::get('citas/{cita}/cancelar', [CitaController::class, 'editCancelar'])->name('citas.cancelar');
        Route::patch('citas/{cita}/cancelar', [CitaController::class, 'cancelar'])->name('citas.cancelar.update');
    });

    // Modulo P4 - Ciclo 2: Reprogramar cita (CU16) - Recepcionista y Administrador
    Route::middleware('rol:Administrador,Recepcionista')->group(function () {
        Route::get('citas/{cita}/reprogramar', [CitaController::class, 'editReprogramar'])->name('citas.reprogramar');
        Route::put('citas/{cita}/reprogramar', [CitaController::class, 'reprogramar'])->name('citas.reprogramar.update');
    });

    // Modulo P5: Agenda y Asistencia (CU18 - CU20)
    Route::middleware('rol:Administrador,Recepcionista,Medico')->group(function () {
        // Agenda del dia por medico y fecha; el medico solo ve la suya (CU20 / RN28)
        Route::get('agenda', [AgendaController::class, 'index'])->name('agenda.index');
    });
    Route::middleware('rol:Administrador,Recepcionista')->group(function () {
        Route::patch('citas/{cita}/confirmar', [CitaEstadoController::class, 'confirmar'])->name('citas.confirmar');          // CU18
        Route::patch('citas/{cita}/inasistencia', [CitaEstadoController::class, 'inasistencia'])->name('citas.inasistencia'); // CU19
        Route::post('agenda/inasistencias', [CitaEstadoController::class, 'inasistenciaMasiva'])->name('agenda.inasistencias'); // CU19 alterno A
    });

    // Modulo P7: Atencion Medica e Historia Clinica (CU23 - CU28)
    Route::middleware('rol:Medico')->group(function () {
        // Registrar atencion medica (CU23)
        Route::get('consultas', [ConsultaController::class, 'index'])->name('consultas.index');
        Route::get('consultas/create', [ConsultaController::class, 'create'])->name('consultas.create');
        Route::post('consultas', [ConsultaController::class, 'store'])->name('consultas.store');
        Route::get('consultas/{consulta}', [ConsultaController::class, 'show'])->name('consultas.show');

        // Alergias, antecedentes y medicacion habitual (CU24)
        Route::get('antecedentes', [AntecedenteAlergiaController::class, 'index'])->name('antecedentes.index');
        Route::post('antecedentes/{paciente}/alergias', [AntecedenteAlergiaController::class, 'storeAlergia'])->name('antecedentes.alergias.store');
        Route::delete('antecedentes/{paciente}/alergias/{alergia}', [AntecedenteAlergiaController::class, 'destroyAlergia'])->name('antecedentes.alergias.destroy');
        Route::post('antecedentes/{paciente}/antecedentes', [AntecedenteAlergiaController::class, 'storeAntecedente'])->name('antecedentes.antecedentes.store');
        Route::delete('antecedentes/registro/{antecedente}', [AntecedenteAlergiaController::class, 'destroyAntecedente'])->name('antecedentes.antecedentes.destroy');
        Route::post('antecedentes/{paciente}/medicamentos', [AntecedenteAlergiaController::class, 'storeMedicamento'])->name('antecedentes.medicamentos.store');
        Route::delete('antecedentes/{paciente}/medicamentos/{medicamento}', [AntecedenteAlergiaController::class, 'destroyMedicamento'])->name('antecedentes.medicamentos.destroy');

        // Emitir receta con verificacion de alergias (CU25, CU26)
        Route::get('recetas', [RecetaController::class, 'index'])->name('recetas.index');
        Route::get('recetas/create', [RecetaController::class, 'create'])->name('recetas.create');
        Route::get('recetas/verificar-alergia', [RecetaController::class, 'verificarAlergia'])->name('recetas.verificarAlergia');
        Route::post('recetas', [RecetaController::class, 'store'])->name('recetas.store');
    });

    // Historia clinica y receta: medico (pacientes de su agenda) y paciente (solo la suya) - CU28 / RN28
    Route::middleware('rol:Medico,Paciente')->group(function () {
        Route::get('historia-clinica', [HistoriaClinicaController::class, 'index'])->name('historia.index');
        Route::get('historia-clinica/{paciente}', [HistoriaClinicaController::class, 'show'])->name('historia.show');
        Route::get('recetas/{receta}', [RecetaController::class, 'show'])->name('recetas.show');
    });

    // Catalogos de medicamentos y alergias (CU27)
    Route::middleware('rol:Administrador')->group(function () {
        Route::get('catalogos', [CatalogoController::class, 'index'])->name('catalogos.index');
        Route::post('catalogos/medicamentos', [CatalogoController::class, 'storeMedicamento'])->name('catalogos.medicamentos.store');
        Route::put('catalogos/medicamentos/{medicamento}', [CatalogoController::class, 'updateMedicamento'])->name('catalogos.medicamentos.update');
        Route::patch('catalogos/medicamentos/{medicamento}/estado', [CatalogoController::class, 'toggleMedicamento'])->name('catalogos.medicamentos.estado');
        Route::post('catalogos/alergias', [CatalogoController::class, 'storeAlergia'])->name('catalogos.alergias.store');
        Route::put('catalogos/alergias/{alergia}', [CatalogoController::class, 'updateAlergia'])->name('catalogos.alergias.update');
        Route::patch('catalogos/alergias/{alergia}/estado', [CatalogoController::class, 'toggleAlergia'])->name('catalogos.alergias.estado');
    });
});
