<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Edicion;
use App\Models\User;
use App\Models\Votacion;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Datos de demostración. Las categorías y opciones son ILUSTRATIVAS:
     * no representan definiciones aprobadas por el cliente (pendiente de validación).
     */
    public function run(): void
    {
        User::updateOrCreate(['email' => 'admin@festival.test'], [
            'name' => 'Administrador Festival', 'password' => 'Admin12345', 'rol' => 'admin',
        ]);
        User::updateOrCreate(['email' => 'votante@festival.test'], [
            'name' => 'Votante Demo', 'password' => 'Votante12345', 'rol' => 'votante',
        ]);

        $edicion = Edicion::updateOrCreate(['anio' => 2027], [
            'nombre' => 'Festival de la Leyenda Vallenata 2027',
            'descripcion' => 'Participa en las votaciones del Festival. Tu voz también hace parte de la leyenda.',
            'activa' => true,
        ]);

        $musica = Categoria::firstOrCreate(['edicion_id' => $edicion->id, 'nombre' => 'Música'], [
            'descripcion' => 'Categoría de ejemplo (pendiente de validación con el cliente).',
        ]);
        Categoria::firstOrCreate(['edicion_id' => $edicion->id, 'nombre' => 'Danza y folclor'], [
            'descripcion' => 'Categoría de ejemplo (pendiente de validación con el cliente).',
        ]);

        if (! Votacion::where('categoria_id', $musica->id)->exists()) {
            $v = Votacion::create([
                'categoria_id' => $musica->id,
                'titulo' => 'Canción favorita del público',
                'descripcion' => 'Ejemplo ilustrativo del flujo Edición → Categoría → Votación → Opción → Voto.',
                'estado' => Votacion::ABIERTA,
                'votos_por_usuario' => 1,
            ]);
            foreach (['Canción A', 'Canción B', 'Canción C'] as $n) {
                $v->opciones()->create(['nombre' => $n]);
            }
        }
    }
}
