<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('votaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_id')->constrained('categorias')->cascadeOnDelete();
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            // borrador | abierta | cerrada
            $table->string('estado', 16)->default('borrador');
            $table->dateTime('apertura')->nullable();
            $table->dateTime('cierre')->nullable();
            $table->unsignedSmallInteger('votos_por_usuario')->default(1);
            $table->boolean('resultados_publicos')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('votaciones');
    }
};
