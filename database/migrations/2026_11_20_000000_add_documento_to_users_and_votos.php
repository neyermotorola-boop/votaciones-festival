<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Una persona = un voto por categoría y edición, sin importar cuántas cuentas intente crear:
     *  - users.documento (único): una sola cuenta por documento de identidad.
     *  - users.email_canonico (único): una sola cuenta por correo real (ignora puntos de Gmail y alias +etiqueta).
     *  - votos.documento + UNIQUE(edicion_id, categoria_id, documento): el voto guarda quién lo emitió y MySQL
     *    rechaza un segundo voto del mismo documento aunque se creara otra cuenta o se cambiara el correo.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('tipo_documento', 4)->nullable()->after('email');
            $table->string('documento', 15)->nullable()->unique()->after('tipo_documento');
            $table->string('email_canonico', 190)->nullable()->unique()->after('email');
        });

        // Cuentas existentes: calcular el correo canónico.
        foreach (DB::table('users')->select('id', 'email')->get() as $u) {
            DB::table('users')->where('id', $u->id)->update(['email_canonico' => User::canonizarEmail($u->email)]);
        }

        Schema::table('votos', function (Blueprint $table) {
            $table->string('documento', 15)->nullable()->after('opcion_id');
            $table->unique(['edicion_id', 'categoria_id', 'documento'], 'votos_unico_documento_edicion_categoria');
        });
    }

    public function down(): void
    {
        Schema::table('votos', function (Blueprint $table) {
            $table->dropUnique('votos_unico_documento_edicion_categoria');
            $table->dropColumn('documento');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['documento']);
            $table->dropUnique(['email_canonico']);
            $table->dropColumn(['tipo_documento', 'documento', 'email_canonico']);
        });
    }
};
