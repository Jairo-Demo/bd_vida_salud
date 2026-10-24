<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('antecedentes', function (Blueprint $table) {
            $table->id();
            // Paciente 1..1 -- 0..* Antecedente
            $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
            // Consulta 0..1 -- 0..* Antecedente (consulta en la que se detecto, opcional)
            $table->foreignId('consulta_id')->nullable()->constrained('consultas')->nullOnDelete();
            // Usuario 0..1 -- 0..* Antecedente (quien lo registro)
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('tipo', ['Personal', 'Familiar', 'Quirurgico', 'Cronico', 'Otro'])->default('Personal');
            $table->text('descripcion');
            $table->date('fecha_registro')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('antecedentes');
    }
};
