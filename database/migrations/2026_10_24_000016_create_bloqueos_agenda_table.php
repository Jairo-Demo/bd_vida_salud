<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bloqueos_agenda', function (Blueprint $table) {
            $table->id();
            // Medico 0..1 -- 0..* BloqueoAgenda (sin medico = bloqueo general, ej. feriado)
            $table->foreignId('medico_id')->nullable()->constrained('medicos')->cascadeOnDelete();
            // Usuario 1..1 -- 0..* BloqueoAgenda (quien registra el bloqueo)
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->time('hora_inicio')->nullable();
            $table->time('hora_fin')->nullable();
            $table->enum('tipo', ['Feriado', 'Vacaciones', 'Permiso', 'Otro'])->default('Otro');
            $table->string('motivo', 255)->nullable();
            $table->boolean('estado')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bloqueos_agenda');
    }
};
