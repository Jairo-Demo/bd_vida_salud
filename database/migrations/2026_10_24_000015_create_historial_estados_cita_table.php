<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historial_estados_cita', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cita_id')->constrained('citas')->onDelete('cascade');
            $table->foreignId('estado_anterior_id')->nullable()->constrained('estados_cita')->nullOnDelete();
            // EstadoCita 1..1 (nuevo) y Usuario 1..1 -- 0..* HistorialEstadoCita
            $table->foreignId('estado_nuevo_id')->constrained('estados_cita')->restrictOnDelete();
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('fecha_hora')->useCurrent();
            $table->text('motivo')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_estados_cita');
    }
};
