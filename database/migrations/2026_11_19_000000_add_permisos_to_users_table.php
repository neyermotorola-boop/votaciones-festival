<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Permisos por usuario: el administrador crea usuarios "gestor" y elige qué puede hacer cada uno.
     * `permisos` guarda la lista de permisos (JSON); `activo` permite desactivar una cuenta sin borrarla.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('permisos')->nullable()->after('rol');
            $table->boolean('activo')->default(true)->after('permisos');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['permisos', 'activo']);
        });
    }
};
