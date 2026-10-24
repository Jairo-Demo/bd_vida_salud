<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recetas', function (Blueprint $table) {
            $table->id();
            // Consulta 1..1 -- 0..1 Receta: una receta como maximo por consulta (RN23)
            $table->foreignId('consulta_id')->unique()->constrained('consultas')->restrictOnDelete();
            $table->string('codigo', 30)->unique();
            $table->date('fecha_emision');
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recetas');
    }
};
