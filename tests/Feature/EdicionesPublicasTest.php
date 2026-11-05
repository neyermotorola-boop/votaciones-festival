<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Edicion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EdicionesPublicasTest extends TestCase
{
    use RefreshDatabase;

    public function test_lista_todas_las_ediciones_y_sus_categorias(): void
    {
        $vieja = Edicion::create(['nombre' => 'Festival 2026', 'anio' => 2026, 'activa' => false]);
        Edicion::create(['nombre' => 'Festival 2027', 'anio' => 2027, 'activa' => true]);
        Categoria::create(['edicion_id' => $vieja->id, 'nombre' => 'Acordeón']);

        $this->get(route('ediciones'))->assertOk()->assertSee('Festival 2026')->assertSee('Festival 2027');
        $this->get(route('edicion', $vieja))->assertOk()->assertSee('Acordeón');
    }
}
