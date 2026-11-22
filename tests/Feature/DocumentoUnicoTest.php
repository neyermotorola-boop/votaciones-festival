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

/** Una persona = una cuenta = un voto por categoría y edición (documento + correo real). */
class DocumentoUnicoTest extends TestCase
{
    use RefreshDatabase;

    private function datos(array $extra = []): array
    {
        return array_merge([
            'name' => 'Persona Uno', 'email' => 'uno@correo.test', 'tipo_documento' => 'CC', 'documento' => '1234567890',
            'password' => 'Clave12345', 'password_confirmation' => 'Clave12345', 'acepta_datos' => '1',
        ], $extra);
    }

    private function votacion(): Votacion
    {
        $e = Edicion::create(['nombre' => 'Festival 2027', 'anio' => 2027, 'activa' => true]);
        $c = Categoria::create(['edicion_id' => $e->id, 'nombre' => 'Música']);
        $v = Votacion::create(['categoria_id' => $c->id, 'titulo' => 'Canción', 'estado' => 'abierta']);
        $v->opciones()->createMany([['nombre' => 'A'], ['nombre' => 'B']]);

        return $v;
    }

    public function test_el_registro_exige_tipo_y_numero_de_documento(): void
    {
        $this->post('/registro', $this->datos(['tipo_documento' => '', 'documento' => '']))
            ->assertSessionHasErrors(['tipo_documento', 'documento']);
        $this->post('/registro', $this->datos(['documento' => '12']))->assertSessionHasErrors('documento');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_el_documento_se_guarda_normalizado(): void
    {
        $this->post('/registro', $this->datos(['documento' => '1.234.567-890']))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['email' => 'uno@correo.test', 'documento' => '1234567890', 'rol' => 'votante']);
    }

    public function test_no_se_puede_crear_otra_cuenta_con_el_mismo_documento(): void
    {
        $this->post('/registro', $this->datos());
        auth()->logout();

        // Otro correo, mismo documento (incluso escrito distinto): se rechaza.
        $this->post('/registro', $this->datos(['email' => 'otro@correo.test', 'documento' => '1 234 567 890']))
            ->assertSessionHasErrors('documento');
        $this->assertDatabaseCount('users', 1);
    }

    public function test_no_se_puede_crear_otra_cuenta_con_el_mismo_correo_aunque_use_alias(): void
    {
        $this->post('/registro', $this->datos(['email' => 'juan.perez@gmail.com']));
        auth()->logout();

        foreach (['juanperez@gmail.com', 'juan.perez+voto2@gmail.com', 'JuanPerez@googlemail.com'] as $i => $alias) {
            $this->post('/registro', $this->datos(['email' => $alias, 'documento' => '99999999'.$i]))
                ->assertSessionHasErrors('email');
        }
        $this->assertDatabaseCount('users', 1);
    }

    public function test_el_voto_guarda_el_documento_de_quien_voto(): void
    {
        $v = $this->votacion();
        $u = User::factory()->create(['documento' => '555666777']);

        $this->actingAs($u)->post(route('votacion.votar', $v), ['opcion_id' => $v->opciones[0]->id]);

        $this->assertDatabaseHas('votos', ['user_id' => $u->id, 'documento' => '555666777']);
    }

    public function test_mysql_rechaza_dos_votos_del_mismo_documento_en_la_misma_categoria(): void
    {
        $v = $this->votacion();
        $a = User::factory()->create(['documento' => '777888999']);
        Voto::create(['user_id' => $a->id, 'votacion_id' => $v->id, 'opcion_id' => $v->opciones[0]->id]);

        // Cuenta "clonada" con otro id pero el mismo documento guardado en el voto: la restricción única lo impide.
        $b = User::factory()->create(['documento' => '111222333']);
        $this->expectException(UniqueConstraintViolationException::class);
        // El modelo toma el documento de la cuenta; se fuerza el duplicado directamente en la base:
        \DB::table('votos')->insert(['user_id' => $b->id, 'edicion_id' => $v->categoria->edicion_id, 'categoria_id' => $v->categoria_id,
            'votacion_id' => $v->id, 'opcion_id' => $v->opciones[1]->id, 'documento' => '777888999', 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_quien_ya_voto_no_vota_de_nuevo_aunque_cambie_su_documento_de_cuenta(): void
    {
        $v = $this->votacion();
        $u = User::factory()->create(['documento' => '321321321']);
        $this->actingAs($u)->post(route('votacion.votar', $v), ['opcion_id' => $v->opciones[0]->id]);

        // Un administrador crea (por error) otra cuenta sin documento y luego se le asigna el mismo documento: no es posible.
        $otra = User::factory()->create(['documento' => null]);
        $this->actingAs(User::factory()->create(['rol' => 'admin']))->put(route('admin.usuarios.update', $otra), [
            'name' => 'Otra', 'email' => $otra->email, 'rol' => 'votante', 'tipo_documento' => 'CC', 'documento' => '321321321',
        ])->assertSessionHasErrors('documento');

        $this->assertDatabaseCount('votos', 1);
    }

    public function test_cuenta_sin_documento_debe_registrarlo_antes_de_votar(): void
    {
        $v = $this->votacion();
        $u = User::factory()->create(['documento' => null, 'tipo_documento' => null]);

        $this->actingAs($u)->post(route('votacion.votar', $v), ['opcion_id' => $v->opciones[0]->id])
            ->assertRedirect(route('documento'));
        $this->assertDatabaseCount('votos', 0);

        $this->actingAs($u)->post(route('documento.guardar'), ['tipo_documento' => 'CC', 'documento' => '444 555 666'])
            ->assertSessionHasNoErrors();
        $this->assertSame('444555666', $u->fresh()->documento);

        $this->actingAs($u->fresh())->post(route('votacion.votar', $v), ['opcion_id' => $v->opciones[0]->id])
            ->assertRedirect(route('votacion.gracias', $v));
    }

    public function test_el_votante_no_puede_cambiar_su_documento_una_vez_registrado(): void
    {
        $u = User::factory()->create(['documento' => '100200300']);
        $this->actingAs($u)->post(route('documento.guardar'), ['tipo_documento' => 'CC', 'documento' => '900800700'])->assertForbidden();
        $this->assertSame('100200300', $u->fresh()->documento);
    }

    public function test_el_admin_puede_buscar_votos_por_documento(): void
    {
        $v = $this->votacion();
        $u = User::factory()->create(['documento' => '246810121']);
        $this->actingAs($u)->post(route('votacion.votar', $v), ['opcion_id' => $v->opciones[0]->id]);

        $this->actingAs(User::factory()->create(['rol' => 'admin']))->get(route('admin.votos', ['q' => '246.810.121']))
            ->assertOk()->assertSee('246810121');
    }
}
