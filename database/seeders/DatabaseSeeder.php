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
            'name' => 'Administrador Festival', 'tipo_documento' => 'CC', 'documento' => '1000000001', 'password' => 'Admin12345', 'rol' => 'admin',
        ]);
        User::updateOrCreate(['email' => 'votante@festival.test'], [
            'name' => 'Votante Demo', 'tipo_documento' => 'CC', 'documento' => '1000000002', 'password' => 'Votante12345', 'rol' => 'votante',
        ]);

        // Gestor de ejemplo: solo puede crear categorías y ver resultados/gráficos (permisos asignados por el admin).
        User::updateOrCreate(['email' => 'gestor@festival.test'], [
            'name' => 'Gestor Demo', 'tipo_documento' => 'CC', 'documento' => '1000000003', 'password' => 'Gestor12345', 'rol' => 'gestor',
            'permisos' => ['categorias', 'resultados'], 'activo' => true,
        ]);

        $edicion = Edicion::updateOrCreate(['anio' => 2027], [
            'nombre' => 'Festival de la Leyenda Vallenata 2027',
            'descripcion' => 'Participa en las votaciones del Festival. Tu voz también hace parte de la leyenda.',
            'activa' => true,
        ]);

        $musica = Categoria::firstOrCreate(['edicion_id' => $edicion->id, 'nombre' => 'Música'], [
            'descripcion' => 'Categoría de ejemplo (pendiente de validación con el cliente).',
        ]);
        $baile = Categoria::firstOrCreate(['edicion_id' => $edicion->id, 'nombre' => 'Danza y folclor'], [
            'descripcion' => 'Categoría de ejemplo (pendiente de validación con el cliente).',
        ]);

        // Una categoría puede tener varios subeventos (votaciones); el usuario solo vota una vez en toda la categoría.
        $this->votacion($musica, 'Canción favorita del público', ['Canción A', 'Canción B', 'Canción C'],
            'Ejemplo ilustrativo del flujo Edición → Categoría → Votación → Opción → Voto.');
        $this->votacion($musica, 'Acordeonero del año', ['Acordeonero 1', 'Acordeonero 2', 'Acordeonero 3'],
            'Segundo subevento de la categoría Música (ejemplo).');
        $this->votacion($baile, 'Mejor pareja de baile', ['Pareja 1', 'Pareja 2', 'Pareja 3'],
            'Ejemplo ilustrativo de otra categoría.');
    }

    /** Crea (sin duplicar) una votación abierta con sus opciones. */
    private function votacion(Categoria $categoria, string $titulo, array $opciones, string $descripcion): void
    {
        $votacion = Votacion::firstOrCreate(
            ['categoria_id' => $categoria->id, 'titulo' => $titulo],
            ['descripcion' => $descripcion, 'estado' => Votacion::ABIERTA],
        );
        if ($votacion->opciones()->doesntExist()) {
            foreach ($opciones as $nombre) {
                $votacion->opciones()->create(['nombre' => $nombre]);
            }
        }
    }
}
