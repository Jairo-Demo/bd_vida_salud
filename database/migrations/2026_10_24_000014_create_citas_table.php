<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('citas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 30)->unique();
            // Paciente 1..1, Medico 1..1, Especialidad 1..1, Ficha 1..1, EstadoCita 1..1 y Usuario 1..1 -- 0..* Cita
            // restrict: no se eliminan registros con citas historicas (RN11)
            $table->foreignId('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->foreignId('medico_id')->constrained('medicos')->restrictOnDelete();
            $table->foreignId('especialidad_id')->constrained('especialidades')->restrictOnDelete();
            $table->foreignId('ficha_id')->constrained('fichas')->restrictOnDelete();
            $table->foreignId('estado_id')->constrained('estados_cita')->restrictOnDelete();
            $table->foreignId('usuario_registro_id')->constrained('users')->restrictOnDelete();
            $table->date('fecha');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->text('motivo')->nullable();
            $table->enum('modalidad', ['Presencial', 'Virtual'])->default('Presencial');
            $table->text('motivo_cancelacion')->nullable();
            $table->timestamp('fecha_creacion')->useCurrent();
            $table->timestamps();
            // Agenda del medico por fecha y ficha (RF07)
            $table->index(['medico_id', 'fecha']);
            $table->index(['ficha_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('citas');
    }
};
