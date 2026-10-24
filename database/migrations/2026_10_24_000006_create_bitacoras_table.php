<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bitacoras', function (Blueprint $table) {
            $table->id();
            // Usuario 1..1 -- 0..* Bitacora
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->string('accion', 100);
            $table->string('tabla_afectada', 50)->nullable();
            $table->unsignedBigInteger('registro_id')->nullable();
            $table->text('detalles')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('canal', 20)->default('web');
            $table->timestamp('fecha_hora')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bitacoras');
    }
};
