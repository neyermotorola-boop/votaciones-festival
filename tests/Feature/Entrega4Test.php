<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Edicion;
use App\Models\User;
use App\Models\Votacion;
use App\Models\Voto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Entrega final (Eje 4): administración dinámica de ediciones sin tocar código y
 * refuerzo de la regla un voto por usuario, categoría y edición.
 */
class Entrega4Test extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['rol' => 'admin']);
    }

    /** Crea edición + categoría + votación abierta con dos opciones. */
    private function escenario(int $anio, string $categoria = 'Música'): Votacion
    {
        $e = Edicion::create(['nombre' => "Festival $anio", 'anio' => $anio]);
        $c = Categoria::create(['edicion_id' => $e->id, 'nombre' => $categoria]);
        $v = Votacion::create(['categoria_id' => $c->id, 'titulo' => 'Canción', 'estado' => 'abierta']);
        $v->opciones()->createMany([['nombre' => 'A'], ['nombre' => 'B']]);

        return $v;
    }

    private function votar(User $u, Votacion $v, int $opcion = 0)
    {
        return $this->actingAs($u)->post(route('votacion.votar', $v), ['opcion_id' => $v->opciones[$opcion]->id]);
    }

    /** Flujo completo desde el panel: edición → categoría → votación → opciones → abrir → votar. */
    public function test_nueva_edicion_se_configura_desde_la_administracion_y_recibe_votos(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.ediciones.store'), [
            'nombre' => 'Festival 2028', 'anio' => 2028, 'activa' => 1,
        ])->assertRedirect(route('admin.ediciones.index'));
        $edicion = Edicion::where('anio', 2028)->firstOrFail();

        $this->actingAs($admin)->post(route('admin.categorias.store'), [
            'edicion_id' => $edicion->id, 'nombre' => 'Rey de Reyes', 'activa' => 1,
        ])->assertSessionHasNoErrors();
        $categoria = Categoria::where('edicion_id', $edicion->id)->firstOrFail();

        $votacion = Votacion::create(['categoria_id' => $categoria->id, 'titulo' => 'Final', 'estado' => 'abierta']);
        $votacion->opciones()->createMany([['nombre' => 'Candidato 1'], ['nombre' => 'Candidato 2']]);

        $this->get(route('home'))->assertOk()->assertSee('Rey de Reyes');
        $this->getJson('/api/categorias?edicion_id='.$edicion->id)->assertOk()->assertJsonPath('data.0.nombre', 'Rey de Reyes');

        $u = User::factory()->create();
        $this->votar($u, $votacion)->assertRedirect(route('votacion.gracias', $votacion));
        $this->assertDatabaseHas('votos', ['user_id' => $u->id, 'edicion_id' => $edicion->id, 'categoria_id' => $categoria->id]);
    }

    /** El límite es por edición: misma categoría en otra edición sí se puede votar; repetir en la misma, no. */
    public function test_la_regla_aplica_por_edicion(): void
    {
        $u = User::factory()->create();
        $v2027 = $this->escenario(2027);
        $v2028 = $this->escenario(2028);

        $this->votar($u, $v2027)->assertRedirect(route('votacion.gracias', $v2027));
        $this->votar($u, $v2028)->assertRedirect(route('votacion.gracias', $v2028));
        $this->votar($u, $v2027, 1)->assertSessionHas('error');

        $this->assertDatabaseCount('votos', 2);
    }

    /** Copiar la estructura de una edición anterior deja todo en borrador y sin votos. */
    public function test_nueva_edicion_puede_copiar_estructura_de_la_anterior(): void
    {
        $v = $this->escenario(2027);
        $vieja = $v->categoria->edicion;
        $this->votar(User::factory()->create(), $v);

        $this->actingAs($this->admin())->post(route('admin.ediciones.store'), [
            'nombre' => 'Festival 2028', 'anio' => 2028, 'copiar_de' => $vieja->id,
        ])->assertSessionHasNoErrors();

        $nueva = Edicion::where('anio', 2028)->firstOrFail();
        $copia = $nueva->categorias()->with('votaciones.opciones')->firstOrFail();
        $this->assertSame('Música', $copia->nombre);
        $this->assertSame(Votacion::BORRADOR, $copia->votaciones[0]->estado);
        $this->assertCount(2, $copia->votaciones[0]->opciones);
        $this->assertSame(0, Voto::where('edicion_id', $nueva->id)->count());
        $this->assertSame(1, Voto::count(), 'los votos de la edición anterior no se tocan');
    }

    public function test_un_votante_no_puede_administrar_ediciones(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('admin.ediciones.store'), ['nombre' => 'X', 'anio' => 2030])->assertForbidden();
        $this->assertDatabaseMissing('ediciones', ['anio' => 2030]);
    }

    /** No se mueve una categoría con votos a otra edición: dejaría votos incoherentes. */
    public function test_no_se_cambia_la_edicion_de_una_categoria_con_votos(): void
    {
        $v = $this->escenario(2027);
        $c = $v->categoria;
        $otra = Edicion::create(['nombre' => 'Festival 2028', 'anio' => 2028]);
        $this->votar(User::factory()->create(), $v);

        $this->actingAs($this->admin())->put(route('admin.categorias.update', $c), ['edicion_id' => $otra->id, 'nombre' => 'Música'])
            ->assertSessionHasErrors('edicion_id');
        $this->assertNotSame($otra->id, $c->fresh()->edicion_id);
    }

    /** El modelo ignora edición/categoría enviadas a mano: siempre las deriva de la votación. */
    public function test_el_modelo_voto_deriva_edicion_y_categoria_de_la_votacion(): void
    {
        $v = $this->escenario(2027);
        $otra = $this->escenario(2028, 'Baile')->categoria;

        $voto = Voto::create([
            'user_id' => User::factory()->create()->id, 'votacion_id' => $v->id, 'opcion_id' => $v->opciones[0]->id,
            'edicion_id' => $otra->edicion_id, 'categoria_id' => $otra->id,
        ]);

        $this->assertSame($v->categoria_id, $voto->categoria_id);
        $this->assertSame($v->categoria->edicion_id, $voto->edicion_id);
    }

    public function test_el_modelo_voto_rechaza_una_opcion_de_otra_votacion(): void
    {
        $v1 = $this->escenario(2027);
        $v2 = $this->escenario(2028);

        $this->expectException(\InvalidArgumentException::class);
        Voto::create(['user_id' => User::factory()->create()->id, 'votacion_id' => $v1->id, 'opcion_id' => $v2->opciones[0]->id]);
    }
}
