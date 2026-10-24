<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paciente_alergia', function (Blueprint $table) {
            $table->id();
            // Clase asociativa Paciente 0..* -- 0..* Alergia
            $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
            $table->foreignId('alergia_id')->constrained('alergias')->restrictOnDelete();
            $table->enum('severidad', ['Leve', 'Moderada', 'Grave'])->default('Leve');
            $table->string('reaccion', 255)->nullable();
            $table->date('fecha_registro')->nullable();
            $table->unique(['paciente_id', 'alergia_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paciente_alergia');
    }
};
