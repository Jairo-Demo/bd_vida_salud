<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paciente_medicamento', function (Blueprint $table) {
            $table->id();
            // Clase asociativa Paciente 0..* -- 0..* Medicamento (medicacion habitual del paciente)
            $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
            $table->foreignId('medicamento_id')->constrained('medicamentos')->restrictOnDelete();
            $table->string('dosis', 100)->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->unique(['paciente_id', 'medicamento_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paciente_medicamento');
    }
};
