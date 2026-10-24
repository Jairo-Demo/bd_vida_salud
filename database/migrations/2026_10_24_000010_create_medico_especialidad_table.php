<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medico_especialidad', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medico_id')->constrained('medicos')->onDelete('cascade');
            $table->foreignId('especialidad_id')->constrained('especialidades')->onDelete('cascade');
            $table->date('fecha_asignacion')->nullable();
            $table->boolean('estado')->default(true);
            $table->unique(['medico_id', 'especialidad_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medico_especialidad');
    }
};
