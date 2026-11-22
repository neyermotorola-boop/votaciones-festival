<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Edicion;
use App\Models\User;
use App\Models\Votacion;
use App\Models\Voto;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pruebas funcionales obligatorias de la Entrega N.º 3 (CP-01 a CP-10).
 * Regla central: un voto por usuario, categoría y edición.
 */
class Entrega3Test extends TestCase
{
    use RefreshDatabase;

    private Edicion $edicion;

    private Categoria $musica;

    private Categoria $baile;

    private Votacion $cancion;

    private Votacion $acordeon;

    private Votacion $pareja;

    protected function setUp(): void
    {
        parent::setUp();

        $this->edicion = Edicion::create(['nombre' => 'Festival 2027', 'anio' => 2027, 'activa' => true]);
        $this->musica = Categoria::create(['edicion_id' => $this->edicion->id, 'nombre' => 'Música']);
        $this->baile = Categoria::create(['edicion_id' => $this->edicion->id, 'nombre' => 'Baile']);

        // La categoría Música tiene dos subeventos (votaciones).
        $this->cancion = $this->votacion($this->musica, 'Canción favorita', ['Canción A', 'Canción B']);
        $this->acordeon = $this->votacion($this->musica, 'Acordeonero del año', ['Acordeonero 1', 'Acordeonero 2']);
        $this->pareja = $this->votacion($this->baile, 'Mejor pareja', ['Pareja 1', 'Pareja 2']);
    }

    private function votacion(Categoria $categoria, string $titulo, array $opciones, string $estado = 'abierta'): Votacion
    {
        $v = Votacion::create(['categoria_id' => $categoria->id, 'titulo' => $titulo, 'estado' => $estado]);
        foreach ($opciones as $nombre) {
            $v->opciones()->create(['nombre' => $nombre]);
        }

        return $v;
    }

    private function votar(User $u, Votacion $v, int $indiceOpcion = 0)
    {
        return $this->actingAs($u)->post(route('votacion.votar', $v), ['opcion_id' => $v->opciones[$indiceOpcion]->id]);
    }

    /** CP-01: usuario autenticado selecciona una opción válida -> se registra un voto. */
    public function test_cp01_voto_valido_se_registra(): void
    {
        $u = User::factory()->create();

        $this->votar($u, $this->cancion)->assertRedirect(route('votacion.gracias', $this->cancion));

        $this->assertDatabaseCount('votos', 1);
        $this->assertDatabaseHas('votos', [
            'user_id' => $u->id,
            'edicion_id' => $this->edicion->id,
            'categoria_id' => $this->musica->id,
            'votacion_id' => $this->cancion->id,
            'opcion_id' => $this->cancion->opciones[0]->id,
        ]);
    }

    /** CP-02: segundo voto en la misma categoría (otra opción o subevento) -> se rechaza. */
    public function test_cp02_segundo_voto_en_la_misma_categoria_es_rechazado(): void
    {
        $u = User::factory()->create();
        $this->votar($u, $this->cancion, 0);

        // Otra opción de la misma votación.
        $this->votar($u, $this->cancion, 1)->assertSessionHas('error');
        // Opción de otro subevento de la misma categoría.
        $this->votar($u, $this->acordeon, 0)->assertSessionHas('error');

        $this->assertDatabaseCount('votos', 1);
    }

    /** CP-03: vota en una categoría diferente -> se permite. */
    public function test_cp03_otra_categoria_se_permite(): void
    {
        $u = User::factory()->create();
        $this->votar($u, $this->cancion);
        $this->votar($u, $this->pareja)->assertRedirect(route('votacion.gracias', $this->pareja));

        $this->assertSame(2, Voto::where('user_id', $u->id)->count());
    }

    /** CP-04: usuario no autenticado intenta votar -> se exige autenticación. */
    public function test_cp04_no_autenticado_debe_iniciar_sesion(): void
    {
        $this->post(route('votacion.votar', $this->cancion), ['opcion_id' => $this->cancion->opciones[0]->id])
            ->assertRedirect(route('login'));
        $this->assertDatabaseCount('votos', 0);
    }

    /** CP-05: intenta votar en categoría no habilitada (o votación cerrada) -> se rechaza. */
    public function test_cp05_categoria_no_habilitada_se_rechaza(): void
    {
        $this->baile->update(['activa' => false]);
        $cerrada = $this->votacion($this->musica, 'Cerrada', ['X', 'Y'], 'cerrada');
        $u = User::factory()->create();

        $this->votar($u, $this->pareja)->assertSessionHas('error');
        $this->votar($u, $cerrada)->assertSessionHas('error');

        $this->assertDatabaseCount('votos', 0);
    }

    /** CP-06: envía una opción que pertenece a otra categoría -> se rechaza. */
    public function test_cp06_opcion_de_otra_categoria_se_rechaza(): void
    {
        $u = User::factory()->create();

        $this->actingAs($u)->post(route('votacion.votar', $this->cancion), ['opcion_id' => $this->pareja->opciones[0]->id])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('votos', 0);
    }

    /** CP-07: consulta sus categorías votadas -> se muestran registros correctos. */
    public function test_cp07_usuario_consulta_sus_categorias_votadas(): void
    {
        $u = User::factory()->create();
        $otro = User::factory()->create();
        $this->votar($u, $this->cancion, 1);
        $this->votar($otro, $this->pareja);

        $this->actingAs($u)->get(route('mis-votos'))
            ->assertOk()
            ->assertSee('Música')
            ->assertSee('Canción B')
            ->assertDontSee('Pareja 1');
    }

    /** CP-08: el administrador consulta los votos -> los datos coinciden con MySQL. */
    public function test_cp08_administrador_consulta_los_votos(): void
    {
        $u = User::factory()->create(['email' => 'ana@correo.test']);
        $this->votar($u, $this->cancion);
        $admin = User::factory()->create(['rol' => 'admin']);

        $resp = $this->actingAs($admin)->get(route('admin.votos'))->assertOk();
        $resp->assertSee('ana@correo.test')->assertSee('Canción A')->assertSee('1 voto(s)');

        $this->actingAs(User::factory()->create(['rol' => 'votante']))->get(route('admin.votos'))->assertForbidden();
    }

    /** CP-09: dos solicitudes a la misma categoría -> solo queda un voto válido. */
    public function test_cp09_la_base_de_datos_impide_votos_duplicados(): void
    {
        $u = User::factory()->create();
        $datos = [
            'user_id' => $u->id, 'edicion_id' => $this->edicion->id, 'categoria_id' => $this->musica->id,
            'votacion_id' => $this->cancion->id, 'opcion_id' => $this->cancion->opciones[0]->id,
        ];
        Voto::create($datos);

        // Aunque una segunda solicitud eludiera las validaciones de la aplicación,
        // la restricción única (usuario, edición, categoría) de MySQL la rechaza.
        $this->expectException(UniqueConstraintViolationException::class);
        Voto::create(['votacion_id' => $this->acordeon->id, 'opcion_id' => $this->acordeon->opciones[0]->id] + $datos);
    }

    /** CP-10: GET /api/categorias/ -> JSON válido con categorías y opciones reales. */
    public function test_cp10_api_devuelve_categorias_y_opciones(): void
    {
        $this->votacion($this->baile, 'Borrador oculto', ['Z1', 'Z2'], 'borrador');

        $resp = $this->getJson('/api/categorias/')->assertOk();

        $resp->assertJsonPath('total', 2)
            ->assertJsonPath('data.0.edicion.anio', 2027)
            ->assertJsonStructure(['total', 'data' => [['id', 'nombre', 'edicion' => ['id', 'anio'], 'votaciones' => [['id', 'titulo', 'estado', 'opciones' => [['id', 'nombre']]]]]]]);

        $texto = json_encode($resp->json(), JSON_UNESCAPED_UNICODE);
        $this->assertStringContainsString('Canción A', $texto);
        $this->assertStringContainsString('Pareja 2', $texto);
        $this->assertStringNotContainsString('Z1', $texto, 'Las votaciones en borrador no se publican.');
    }
}
