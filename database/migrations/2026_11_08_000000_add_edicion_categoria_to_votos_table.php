<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Entrega 3: el voto vincula usuario, edición, categoría, votación y opción.
     * Regla central: un solo voto por usuario, categoría y edición (restricción única en MySQL).
     */
    public function up(): void
    {
        Schema::table('votos', function (Blueprint $table) {
            $table->foreignId('edicion_id')->nullable()->after('user_id')->constrained('ediciones')->cascadeOnDelete();
            $table->foreignId('categoria_id')->nullable()->after('edicion_id')->constrained('categorias')->cascadeOnDelete();
        });

        // Completa los votos ya existentes con la categoría y la edición de su votación.
        DB::statement('UPDATE votos SET categoria_id = (SELECT categoria_id FROM votaciones WHERE votaciones.id = votos.votacion_id)');
        DB::statement('UPDATE votos SET edicion_id = (SELECT edicion_id FROM categorias WHERE categorias.id = votos.categoria_id)');

        Schema::table('votos', function (Blueprint $table) {
            $table->unsignedBigInteger('edicion_id')->nullable(false)->change();
            $table->unsignedBigInteger('categoria_id')->nullable(false)->change();
            $table->unique(['user_id', 'edicion_id', 'categoria_id'], 'votos_unico_usuario_edicion_categoria');
        });
    }

    public function down(): void
    {
        Schema::table('votos', function (Blueprint $table) {
            $table->dropUnique('votos_unico_usuario_edicion_categoria');
            $table->dropConstrainedForeignId('categoria_id');
            $table->dropConstrainedForeignId('edicion_id');
        });
    }
};
