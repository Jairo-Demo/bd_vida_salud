<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pacientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('persona_id')->unique()->constrained('personas')->onDelete('cascade');
            $table->string('codigo', 30)->unique();
            $table->date('fecha_nacimiento');
            $table->enum('sexo', ['M', 'F', 'Otro'])->default('M');
            $table->string('direccion', 255)->nullable();
            $table->string('tipo_sangre', 10)->nullable();
            $table->text('otras_alergias')->nullable();
            $table->string('contacto_emerg_nombre', 150)->nullable();
            $table->string('contacto_emerg_parentesco', 50)->nullable();
            $table->string('contacto_emerg_telefono', 20)->nullable();
            $table->boolean('estado')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pacientes');
    }
};
