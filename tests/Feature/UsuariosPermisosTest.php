<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Edicion;
use App\Models\User;
use App\Models\Votacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Usuarios con permisos individuales (gestor) y gráficos de votación del panel. */
class UsuariosPermisosTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['rol' => 'admin']);
    }

    private function gestor(array $permisos): User
    {
        return User::factory()->create(['rol' => 'gestor', 'permisos' => $permisos]);
    }

    private function votacionConVotos(int $anio = 2027): Votacion
    {
        $e = Edicion::create(['nombre' => "Festival $anio", 'anio' => $anio, 'activa' => true]);
        $c = Categoria::create(['edicion_id' => $e->id, 'nombre' => 'Música']);
        $v = Votacion::create(['categoria_id' => $c->id, 'titulo' => 'Canción', 'estado' => 'abierta']);
        $v->opciones()->createMany([['nombre' => 'A'], ['nombre' => 'B']]);
        foreach ([0, 0, 1] as $i) {
            $this->actingAs(User::factory()->create())->post(route('votacion.votar', $v), ['opcion_id' => $v->opciones[$i]->id]);
        }

        return $v;
    }

    public function test_admin_crea_un_gestor_con_permisos_elegidos(): void
    {
        $this->actingAs($this->admin())->post(route('admin.usuarios.store'), [
            'name' => 'Ana Gestora', 'email' => 'ana@festival.test', 'password' => 'Clave12345', 'rol' => 'gestor',
            'permisos' => ['categorias', 'resultados'],
        ])->assertRedirect(route('admin.usuarios.index'));

        $ana = User::where('email', 'ana@festival.test')->firstOrFail();
        $this->assertSame('gestor', $ana->rol);
        $this->assertSame(['categorias', 'resultados'], $ana->permisos);
        $this->assertTrue($ana->puede('categorias'));
        $this->assertFalse($ana->puede('ediciones'));
        $this->assertNotSame('Clave12345', $ana->password, 'la contraseña se guarda cifrada');
    }

    public function test_el_gestor_solo_accede_a_lo_que_tiene_permitido(): void
    {
        $g = $this->gestor(['categorias', 'resultados']);

        $this->actingAs($g)->get(route('admin.panel'))->assertOk();
        $this->actingAs($g)->get(route('admin.categorias.index'))->assertOk();
        $this->actingAs($g)->get(route('admin.graficos'))->assertOk();
        $this->actingAs($g)->get(route('admin.votos'))->assertOk();
        // Sin permiso:
        $this->actingAs($g)->get(route('admin.ediciones.index'))->assertForbidden();
        $this->actingAs($g)->get(route('admin.votaciones.index'))->assertForbidden();
        $this->actingAs($g)->get(route('admin.reportes'))->assertForbidden();
        $this->actingAs($g)->get(route('admin.usuarios.index'))->assertForbidden();
        $this->actingAs($g)->post(route('admin.ediciones.store'), ['nombre' => 'X', 'anio' => 2040])->assertForbidden();
        $this->assertDatabaseMissing('ediciones', ['anio' => 2040]);
    }

    public function test_gestor_con_permiso_de_categorias_puede_crear_una(): void
    {
        $e = Edicion::create(['nombre' => 'Festival 2027', 'anio' => 2027]);

        $this->actingAs($this->gestor(['categorias']))->post(route('admin.categorias.store'), ['edicion_id' => $e->id, 'nombre' => 'Baile'])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('categorias', ['nombre' => 'Baile']);
    }

    public function test_gestor_sin_permisos_no_entra_al_panel(): void
    {
        $this->actingAs($this->gestor([]))->get(route('admin.panel'))->assertForbidden();
    }

    public function test_votante_no_accede_a_usuarios_ni_graficos(): void
    {
        $v = User::factory()->create();
        $this->actingAs($v)->get(route('admin.usuarios.index'))->assertForbidden();
        $this->actingAs($v)->get(route('admin.graficos'))->assertForbidden();
    }

    public function test_usuario_desactivado_no_puede_iniciar_sesion(): void
    {
        $u = User::factory()->create(['email' => 'baja@festival.test', 'password' => 'Clave12345', 'activo' => false]);

        $this->post('/ingresar', ['email' => 'baja@festival.test', 'password' => 'Clave12345'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_admin_activa_y_desactiva_pero_no_a_si_mismo(): void
    {
        $admin = $this->admin();
        $g = $this->gestor(['resultados']);

        $this->actingAs($admin)->post(route('admin.usuarios.estado', $g))->assertSessionHas('ok');
        $this->assertFalse($g->fresh()->estaActivo());
        $this->actingAs($g->fresh())->get(route('admin.graficos'))->assertForbidden();

        $this->actingAs($admin)->post(route('admin.usuarios.estado', $admin))->assertSessionHas('error');
        $this->assertTrue($admin->fresh()->estaActivo());
    }

    public function test_validaciones_al_crear_usuarios(): void
    {
        $admin = $this->admin();
        User::factory()->create(['email' => 'dup@festival.test']);

        $this->actingAs($admin)->post(route('admin.usuarios.store'), [
            'name' => 'X', 'email' => 'dup@festival.test', 'password' => 'corta', 'rol' => 'gestor', 'permisos' => ['volar'],
        ])->assertSessionHasErrors(['email', 'password', 'permisos.0']);
    }

    public function test_admin_no_puede_quitarse_su_propio_rol(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->put(route('admin.usuarios.update', $admin), [
            'name' => $admin->name, 'email' => $admin->email, 'rol' => 'votante', 'tipo_documento' => 'CC', 'documento' => $admin->documento,
        ])->assertSessionHasErrors('rol');
        $this->assertSame('admin', $admin->fresh()->rol);
    }

    public function test_los_graficos_muestran_los_conteos_reales(): void
    {
        $v = $this->votacionConVotos();

        $r = $this->actingAs($this->admin())->get(route('admin.graficos', ['votacion_id' => $v->id]))->assertOk();
        $datos = $r->viewData('datos');

        $this->assertSame(3, $datos['resumen']['votos']);
        $this->assertSame([2, 1], $datos['opciones']['data']);
        $this->assertSame([66.7, 33.3], $datos['opciones']['porcentajes']);
        $this->assertSame(['Música'], $datos['categorias']['labels']);
        $this->assertSame([3], $datos['categorias']['data']);
        $this->assertSame(3, array_sum($datos['dias']['data']));
        $this->assertCount(14, $datos['dias']['labels']);
    }

    public function test_el_panel_muestra_graficos_solo_con_permiso_de_resultados(): void
    {
        $this->votacionConVotos();

        $this->actingAs($this->gestor(['categorias']))->get(route('admin.panel'))->assertOk()->assertDontSee('pDias');
        $this->actingAs($this->gestor(['resultados']))->get(route('admin.panel'))->assertOk()->assertSee('pDias');
    }
}
