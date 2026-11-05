<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Edicion;
use App\Models\User;
use App\Models\Votacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VotacionTest extends TestCase
{
    use RefreshDatabase;

    private function votacion(string $estado = 'abierta', int $opciones = 3, int $limite = 1): Votacion
    {
        $ed = Edicion::firstOrCreate(['anio' => 2027], ['nombre' => 'Fest 2027', 'activa' => true]);
        $cat = Categoria::firstOrCreate(['edicion_id' => $ed->id, 'nombre' => 'Música']);
        $v = Votacion::create(['categoria_id' => $cat->id, 'titulo' => 'T', 'estado' => $estado, 'votos_por_usuario' => $limite]);
        for ($i = 1; $i <= $opciones; $i++) {
            $v->opciones()->create(['nombre' => "Op $i"]);
        }

        return $v;
    }

    public function test_invitado_no_puede_votar(): void
    {
        $v = $this->votacion();
        $this->post(route('votacion.votar', $v), ['opcion_id' => $v->opciones->first()->id])->assertRedirect(route('login'));
        $this->assertDatabaseCount('votos', 0);
    }

    public function test_votante_registra_voto(): void
    {
        $v = $this->votacion();
        $u = User::factory()->create();
        $this->actingAs($u)->post(route('votacion.votar', $v), ['opcion_id' => $v->opciones->first()->id])
            ->assertRedirect(route('votacion.gracias', $v));
        $this->assertDatabaseCount('votos', 1);
    }

    public function test_no_se_puede_exceder_el_limite_de_votos(): void
    {
        $v = $this->votacion();
        $u = User::factory()->create();
        [$a, $b] = [$v->opciones[0], $v->opciones[1]];
        $this->actingAs($u)->post(route('votacion.votar', $v), ['opcion_id' => $a->id]);
        $this->actingAs($u)->post(route('votacion.votar', $v), ['opcion_id' => $b->id])->assertSessionHas('error');
        $this->assertDatabaseCount('votos', 1);
    }

    public function test_no_se_vota_en_votacion_cerrada_o_borrador(): void
    {
        foreach (['cerrada', 'borrador'] as $estado) {
            $v = $this->votacion($estado);
            $this->actingAs(User::factory()->create())
                ->post(route('votacion.votar', $v), ['opcion_id' => $v->opciones->first()->id])->assertSessionHas('error');
        }
        $this->assertDatabaseCount('votos', 0);
    }

    public function test_opcion_de_otra_votacion_es_rechazada(): void
    {
        $v1 = $this->votacion();
        $otra = $v1->categoria->votaciones()->create(['titulo' => 'Otra', 'estado' => 'abierta']);
        $op = $otra->opciones()->create(['nombre' => 'X']);
        $this->actingAs(User::factory()->create())
            ->post(route('votacion.votar', $v1), ['opcion_id' => $op->id])->assertSessionHas('error');
        $this->assertDatabaseCount('votos', 0);
    }

    public function test_votante_no_accede_al_admin(): void
    {
        $this->actingAs(User::factory()->create(['rol' => 'votante']))->get('/admin')->assertForbidden();
        $this->actingAs(User::factory()->create(['rol' => 'admin']))->get('/admin')->assertOk();
    }

    public function test_no_se_abre_con_menos_de_dos_opciones(): void
    {
        $v = $this->votacion('borrador', 1);
        $this->actingAs(User::factory()->create(['rol' => 'admin']))
            ->post(route('admin.votaciones.estado', $v), ['estado' => 'abierta'])->assertSessionHas('error');
        $this->assertSame('borrador', $v->fresh()->estado);
    }

    public function test_paginas_publicas_cargan(): void
    {
        $v = $this->votacion();
        $this->get('/')->assertOk()->assertSee('Fest 2027');
        $this->get(route('categoria', $v->categoria))->assertOk();
        $this->get(route('votacion', $v))->assertOk()->assertSee('Op 1');
    }
}
