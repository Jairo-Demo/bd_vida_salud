<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detalle_recetas', function (Blueprint $table) {
            $table->id();
            // Receta 1..1 <>-- 1..* DetalleReceta (composicion: el detalle se elimina con su receta)
            $table->foreignId('receta_id')->constrained('recetas')->cascadeOnDelete();
            // Medicamento 1..1 -- 0..* DetalleReceta (Receta y Medicamento se relacionan solo a traves del detalle)
            $table->foreignId('medicamento_id')->constrained('medicamentos')->restrictOnDelete();
            $table->string('dosis', 100);
            $table->string('frecuencia', 100);
            $table->string('duracion_tratamiento', 100)->nullable();
            $table->unsignedInteger('cantidad')->default(1);
            $table->text('indicaciones')->nullable();
            $table->unique(['receta_id', 'medicamento_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_recetas');
    }
};
