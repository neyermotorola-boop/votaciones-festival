<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Edicion;
use App\Models\User;
use App\Models\Votacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OpcionImagenTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('uploads');
        $this->admin = User::factory()->create(['rol' => 'admin']);
    }

    private function votacion(bool $conFoto): Votacion
    {
        $ed = Edicion::firstOrCreate(['anio' => 2027], ['nombre' => 'F', 'activa' => true]);
        $cat = Categoria::create(['edicion_id' => $ed->id, 'nombre' => $conFoto ? 'Con foto' : 'Texto', 'opciones_con_imagen' => $conFoto]);

        return Votacion::create(['categoria_id' => $cat->id, 'titulo' => 'V', 'estado' => 'abierta']);
    }

    public function test_categoria_solo_texto_sigue_funcionando_sin_foto(): void
    {
        $v = $this->votacion(false);
        $this->actingAs($this->admin)->post(route('admin.opciones.store', $v), ['nombre' => 'Canción A'])->assertSessionHas('ok');
        $this->assertNull($v->opciones()->first()->imagen);
    }

    public function test_categoria_con_foto_exige_imagen(): void
    {
        $v = $this->votacion(true);
        $this->actingAs($this->admin)->post(route('admin.opciones.store', $v), ['nombre' => 'Juan'])->assertSessionHasErrors('imagen');
        $this->assertSame(0, $v->opciones()->count());
    }

    public function test_sube_foto_y_se_muestra_con_el_nombre(): void
    {
        $v = $this->votacion(true);
        $this->actingAs($this->admin)->post(route('admin.opciones.store', $v), [
            'nombre' => 'Juan Acordeón', 'imagen' => UploadedFile::fake()->image('juan.jpg', 400, 400),
        ])->assertSessionHas('ok');

        $op = $v->opciones()->first();
        Storage::disk('uploads')->assertExists($op->imagen);
        $this->assertStringStartsWith('opciones/', $op->imagen);

        $this->get(route('votacion', $v))->assertOk()->assertSee('Juan Acordeón')->assertSee($op->imagen_url, false);
    }

    public function test_rechaza_archivos_que_no_son_imagen(): void
    {
        $v = $this->votacion(true);
        $this->actingAs($this->admin)->post(route('admin.opciones.store', $v), [
            'nombre' => 'X', 'imagen' => UploadedFile::fake()->create('virus.php', 10, 'application/x-php'),
        ])->assertSessionHasErrors('imagen');
        $this->actingAs($this->admin)->post(route('admin.opciones.store', $v), [
            'nombre' => 'X', 'imagen' => UploadedFile::fake()->create('logo.svg', 5, 'image/svg+xml'),
        ])->assertSessionHasErrors('imagen');
    }

    public function test_reemplazar_y_quitar_foto(): void
    {
        $v = $this->votacion(true);
        $this->actingAs($this->admin)->post(route('admin.opciones.store', $v), [
            'nombre' => 'Juan', 'imagen' => UploadedFile::fake()->image('a.png', 200, 200),
        ]);
        $op = $v->opciones()->first();
        $vieja = $op->imagen;

        $this->put(route('admin.opciones.update', [$v, $op]), [
            'nombre' => 'Juan', 'imagen' => UploadedFile::fake()->image('b.webp', 200, 200),
        ])->assertSessionHas('ok');
        $op->refresh();
        Storage::disk('uploads')->assertMissing($vieja);
        Storage::disk('uploads')->assertExists($op->imagen);

        $this->put(route('admin.opciones.update', [$v, $op]), ['nombre' => 'Juan', 'quitar_imagen' => 1]);
        $this->assertNull($op->fresh()->imagen);
    }

    public function test_no_se_renombra_opcion_con_votos(): void
    {
        $v = $this->votacion(false);
        $op = $v->opciones()->create(['nombre' => 'Original']);
        $v->votos()->create(['user_id' => User::factory()->create()->id, 'opcion_id' => $op->id]);

        $this->actingAs($this->admin)->put(route('admin.opciones.update', [$v, $op]), ['nombre' => 'Cambiado'])->assertSessionHas('error');
        $this->assertSame('Original', $op->fresh()->nombre);
    }

    public function test_interruptor_de_foto_en_categoria(): void
    {
        $ed = Edicion::create(['nombre' => 'F', 'anio' => 2028]);
        $this->actingAs($this->admin)->post(route('admin.categorias.store'), [
            'edicion_id' => $ed->id, 'nombre' => 'Concursantes', 'activa' => 1, 'opciones_con_imagen' => 1,
        ]);
        $this->assertTrue(Categoria::where('nombre', 'Concursantes')->first()->opciones_con_imagen);
    }
}
