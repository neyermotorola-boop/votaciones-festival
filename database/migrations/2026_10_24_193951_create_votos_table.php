<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('votos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('votacion_id')->constrained('votaciones')->cascadeOnDelete();
            $table->foreignId('opcion_id')->constrained('opciones')->cascadeOnDelete();
            $table->timestamps();
            // un usuario no puede votar dos veces por la misma opción
            $table->unique(['user_id', 'votacion_id', 'opcion_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('votos');
    }
};
