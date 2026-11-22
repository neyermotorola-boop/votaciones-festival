<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_selector_de_tema_en_sitio_publico_acceso_y_panel(): void
    {
        foreach (['/', '/ingresar', '/registro'] as $url) {
            $this->get($url)->assertOk()->assertSee('data-tema-toggle', false)->assertSee('js/tema.js', false);
        }

        $admin = User::factory()->create(['rol' => 'admin']);
        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('data-tema-toggle', false);
    }
}
