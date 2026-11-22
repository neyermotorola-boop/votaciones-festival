<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categorias', function (Blueprint $table) {
            // Si es true, los participantes de esta categoría se registran con foto.
            $table->boolean('opciones_con_imagen')->default(false)->after('activa');
        });
    }

    public function down(): void
    {
        Schema::table('categorias', function (Blueprint $table) {
            $table->dropColumn('opciones_con_imagen');
        });
    }
};
