<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('habitaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tipo_habitacion_id')->constrained('tipo_habitaciones');
            $table->string('numero')->unique();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->unsignedInteger('precio')->default(0);
            $table->enum('estado', ['disponible', 'ocupada', 'limpieza', 'bloqueada'])->default('disponible');
            $table->boolean('activa')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('habitaciones');
    }
};
