<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicamentos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_comercial', 150);
            $table->string('principio_activo', 150);
            $table->string('presentacion', 100)->nullable();
            $table->boolean('estado')->default(true);
            $table->unique(['nombre_comercial', 'presentacion']);
            $table->index('principio_activo');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicamentos');
    }
};
