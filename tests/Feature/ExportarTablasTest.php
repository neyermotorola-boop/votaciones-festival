<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Edicion;
use App\Models\User;
use App\Models\Votacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use OpenSpout\Reader\XLSX\Reader;
use Tests\TestCase;

class ExportarTablasTest extends TestCase
{
    use RefreshDatabase;

    private Votacion $v;

    protected function setUp(): void
    {
        parent::setUp();
        $ed = Edicion::create(['nombre' => 'Festival 2027', 'anio' => 2027, 'activa' => true]);
        $cat = Categoria::create(['edicion_id' => $ed->id, 'nombre' => 'Música']);
        $this->v = Votacion::create(['categoria_id' => $cat->id, 'titulo' => 'Canción del año', 'estado' => 'abierta']);
        $a = $this->v->opciones()->create(['nombre' => 'Canción A']);
        $this->v->opciones()->create(['nombre' => 'Canción B']);
        $baile = Categoria::create(['edicion_id' => $ed->id, 'nombre' => 'Baile']);
        $otra = Votacion::create(['categoria_id' => $baile->id, 'titulo' => 'Otra', 'estado' => 'abierta']);
        $x = $otra->opciones()->create(['nombre' => 'Opción X']);

        $ana = User::factory()->create(['name' => 'Ana Votante', 'rol' => 'votante']);
        $luis = User::factory()->create(['name' => 'Luis Votante', 'rol' => 'votante']);
        $this->v->votos()->create(['user_id' => $ana->id, 'opcion_id' => $a->id]);
        $otra->votos()->create(['user_id' => $luis->id, 'opcion_id' => $x->id]);
    }

    private function filas($resp): array
    {
        $reader = new Reader();
        $reader->open($resp->baseResponse->getFile()->getPathname());
        $filas = [];
        foreach ($reader->getSheetIterator() as $hoja) {
            foreach ($hoja->getRowIterator() as $fila) {
                $filas[] = array_map(fn ($c) => $c->getValue(), $fila->cells);
            }
        }
        $reader->close();

        return $filas;
    }

    public function test_todas_las_tablas_exportan(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);
        $rutas = [
            route('admin.exportar.ediciones') => 'Festival 2027',
            route('admin.exportar.categorias') => 'Música',
            route('admin.exportar.votaciones') => 'Canción del año',
            route('admin.exportar.opciones', $this->v) => 'Canción A',
            route('admin.exportar.votos') => 'Ana Votante',
            route('admin.exportar.votantes') => 'Luis Votante',
            route('admin.exportar.bitacora') => 'Acción',
        ];
        foreach ($rutas as $url => $esperado) {
            $resp = $this->actingAs($admin)->get($url);
            $resp->assertOk();
            $this->assertStringContainsString('.xlsx', $resp->headers->get('content-disposition'), $url);
            $this->assertStringContainsString($esperado, json_encode($this->filas($resp), JSON_UNESCAPED_UNICODE), $url);
        }
    }

    public function test_exportar_votos_respeta_filtros(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);
        $filas = $this->filas($this->actingAs($admin)->get(route('admin.exportar.votos', ['categoria_id' => $this->v->categoria_id])));
        $texto = json_encode($filas, JSON_UNESCAPED_UNICODE);

        $this->assertCount(2, $filas); // cabecera + 1 voto
        $this->assertStringContainsString('Ana Votante', $texto);
        $this->assertStringNotContainsString('Luis Votante', $texto);

        $filas = $this->filas($this->actingAs($admin)->get(route('admin.exportar.votos', ['q' => 'luis votante'])));
        $this->assertCount(2, $filas);
    }

    public function test_seccion_votos_lista_y_filtra(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);
        $this->actingAs($admin)->get(route('admin.votos'))->assertOk()->assertSee('Ana Votante')->assertSee('Luis Votante');
        $this->actingAs($admin)->get(route('admin.votos', ['q' => 'ana votante']))->assertOk()->assertSee('Ana Votante')->assertDontSee('Luis Votante');
    }

    public function test_votante_no_puede_exportar(): void
    {
        $votante = User::factory()->create(['rol' => 'votante']);
        $this->actingAs($votante)->get(route('admin.exportar.votos'))->assertForbidden();
        $this->actingAs($votante)->get(route('admin.votos'))->assertForbidden();
    }
}
