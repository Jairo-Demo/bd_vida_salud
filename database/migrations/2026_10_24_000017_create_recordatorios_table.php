<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recordatorios', function (Blueprint $table) {
            $table->id();
            // Cita 1..1 -- 0..* Recordatorio
            $table->foreignId('cita_id')->constrained('citas')->cascadeOnDelete();
            $table->enum('tipo', ['Proxima cita', 'Reprogramacion', 'Cancelacion'])->default('Proxima cita');
            $table->text('mensaje');
            $table->dateTime('fecha_programada');
            $table->dateTime('fecha_envio')->nullable();
            $table->enum('estado', ['Pendiente', 'Enviado', 'Fallido'])->default('Pendiente');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recordatorios');
    }
};
