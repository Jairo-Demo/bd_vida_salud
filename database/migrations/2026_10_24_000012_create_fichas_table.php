<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fichas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('horario_id')->constrained('horarios_atencion')->onDelete('cascade');
            $table->integer('numero');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->boolean('estado')->default(true);
            $table->unique(['horario_id', 'numero']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fichas');
    }
};
