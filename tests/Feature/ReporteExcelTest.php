<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Edicion;
use App\Models\User;
use App\Models\Votacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use OpenSpout\Reader\XLSX\Reader;
use Tests\TestCase;

class ReporteExcelTest extends TestCase
{
    use RefreshDatabase;

    private function escenario(): array
    {
        $edicion = Edicion::create(['nombre' => 'Festival 2027', 'anio' => 2027, 'activa' => true]);
        $canciones = Categoria::create(['edicion_id' => $edicion->id, 'nombre' => 'Canciones']);
        $baile = Categoria::create(['edicion_id' => $edicion->id, 'nombre' => 'Baile']);

        $vc = Votacion::create(['categoria_id' => $canciones->id, 'titulo' => 'Cancion del ano', 'estado' => 'abierta']);
        $a = $vc->opciones()->create(['nombre' => 'Cancion A']);
        $vc->opciones()->create(['nombre' => 'Cancion B']);
        $vb = Votacion::create(['categoria_id' => $baile->id, 'titulo' => 'Mejor pareja', 'estado' => 'abierta']);
        $vb->opciones()->create(['nombre' => 'Pareja 1']);

        $votante = User::factory()->create(['rol' => 'votante']);
        $vc->votos()->create(['user_id' => $votante->id, 'opcion_id' => $a->id]);

        return [$edicion, $canciones, $baile];
    }

    private function celdas(string $ruta): array
    {
        $reader = new Reader();
        $reader->open($ruta);
        $libro = [];
        foreach ($reader->getSheetIterator() as $hoja) {
            foreach ($hoja->getRowIterator() as $fila) {
                $libro[$hoja->getName()][] = array_map(fn ($c) => $c->getValue(), $fila->cells);
            }
        }
        $reader->close();

        return $libro;
    }

    public function test_solo_admin_puede_descargar(): void
    {
        [$edicion] = $this->escenario();
        $votante = User::factory()->create(['rol' => 'votante']);

        $this->get(route('admin.reportes.excel', ['edicion_id' => $edicion->id]))->assertRedirect();
        $this->actingAs($votante)->get(route('admin.reportes.excel', ['edicion_id' => $edicion->id]))->assertForbidden();
    }

    public function test_excel_filtra_por_categoria(): void
    {
        [$edicion, $canciones] = $this->escenario();
        $admin = User::factory()->create(['rol' => 'admin']);

        $resp = $this->actingAs($admin)->get(route('admin.reportes.excel', [
            'edicion_id' => $edicion->id, 'categoria_id' => $canciones->id, 'incluir_votos' => 1,
        ]));
        $resp->assertOk();
        $this->assertStringContainsString('canciones', $resp->headers->get('content-disposition'));

        $libro = $this->celdas($resp->baseResponse->getFile()->getPathname());
        $this->assertSame(['Resumen', 'Resultados', 'Votaciones', 'Detalle de votos'], array_keys($libro));

        $texto = json_encode($libro, JSON_UNESCAPED_UNICODE);
        $this->assertStringContainsString('Cancion A', $texto);
        $this->assertStringNotContainsString('Pareja 1', $texto);
        $this->assertCount(3, $libro['Resultados']);       // cabecera + 2 opciones
        $this->assertSame(1, $libro['Resultados'][1][5]);  // votos de la opción ganadora
        $this->assertCount(2, $libro['Detalle de votos']); // cabecera + 1 voto
    }

    public function test_excel_de_todas_las_categorias_sin_detalle(): void
    {
        [$edicion] = $this->escenario();
        $admin = User::factory()->create(['rol' => 'admin']);

        $resp = $this->actingAs($admin)->get(route('admin.reportes.excel', ['edicion_id' => $edicion->id]));
        $libro = $this->celdas($resp->baseResponse->getFile()->getPathname());

        $this->assertArrayNotHasKey('Detalle de votos', $libro);
        $this->assertStringContainsString('Pareja 1', json_encode($libro));
    }
}
