<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultas', function (Blueprint $table) {
            $table->id();
            // Cita 1..1 -- 0..1 Consulta: unique garantiza una consulta como maximo por cita (RN22)
            $table->foreignId('cita_id')->unique()->constrained('citas')->restrictOnDelete();
            $table->time('hora_inicio_real')->nullable();
            $table->time('hora_fin_real')->nullable();
            $table->text('sintomas')->nullable();
            $table->text('diagnostico')->nullable();
            $table->text('observaciones')->nullable();
            $table->decimal('peso', 5, 2)->nullable();
            $table->string('presion_arterial', 15)->nullable();
            $table->decimal('temperatura', 4, 1)->nullable();
            $table->text('indicaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultas');
    }
};
