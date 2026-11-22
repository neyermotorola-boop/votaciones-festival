<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Edicion;
use App\Models\User;
use App\Models\Votacion;
use App\Notifications\Bienvenida;
use App\Notifications\RestablecerContrasena;
use App\Notifications\VotoRegistrado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class NotificacionesTest extends TestCase
{
    use RefreshDatabase;

    public function test_registro_envia_bienvenida(): void
    {
        Notification::fake();

        $this->post('/registro', [
            'name' => 'Ana Pérez', 'email' => 'ana@test.co',
            'password' => 'Secreta123', 'password_confirmation' => 'Secreta123', 'acepta_datos' => '1', 'tipo_documento' => 'CC', 'documento' => '102030401',
        ])->assertRedirect(route('home'));

        Notification::assertSentTo(User::where('email', 'ana@test.co')->first(), Bienvenida::class);
    }

    public function test_voto_envia_comprobante_con_la_opcion(): void
    {
        Notification::fake();
        $ed = Edicion::create(['nombre' => 'F', 'anio' => 2027, 'activa' => true]);
        $cat = Categoria::create(['edicion_id' => $ed->id, 'nombre' => 'Música']);
        $v = Votacion::create(['categoria_id' => $cat->id, 'titulo' => 'Canción', 'estado' => 'abierta']);
        $op = $v->opciones()->create(['nombre' => 'La gota fría']);
        $v->opciones()->create(['nombre' => 'Otra']);
        $u = User::factory()->create();

        $this->actingAs($u)->post(route('votacion.votar', $v), ['opcion_id' => $op->id]);

        Notification::assertSentTo($u, VotoRegistrado::class, function ($n) use ($u, $op) {
            $html = (string) $n->toMail($u)->render();

            return $n->voto->opcion_id === $op->id && str_contains($html, 'La gota fría');
        });
    }

    public function test_sin_notificaciones_no_se_envia_nada(): void
    {
        config(['festival.notificaciones_correo' => false]);
        Notification::fake();

        $this->post('/registro', [
            'name' => 'Ana', 'email' => 'ana2@test.co',
            'password' => 'Secreta123', 'password_confirmation' => 'Secreta123', 'acepta_datos' => '1', 'tipo_documento' => 'CC', 'documento' => '102030402',
        ]);

        Notification::assertNothingSent();
        $this->assertDatabaseHas('users', ['email' => 'ana2@test.co']);
    }

    public function test_fallo_del_servidor_de_correo_no_impide_el_registro(): void
    {
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => 'servidor-inexistente.invalid', 'mail.mailers.smtp.port' => 2525]);

        $this->post('/registro', [
            'name' => 'Ana', 'email' => 'ana3@test.co',
            'password' => 'Secreta123', 'password_confirmation' => 'Secreta123', 'acepta_datos' => '1', 'tipo_documento' => 'CC', 'documento' => '102030403',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticated();
    }

    public function test_recuperar_contrasena(): void
    {
        Notification::fake();
        $u = User::factory()->create(['email' => 'luis@test.co']);

        $this->post(route('password.email'), ['email' => 'luis@test.co'])->assertSessionHas('ok');
        // Un correo inexistente recibe la misma respuesta (no revela qué cuentas existen).
        $this->post(route('password.email'), ['email' => 'nadie@test.co'])->assertSessionHas('ok');

        $token = null;
        Notification::assertSentTo($u, RestablecerContrasena::class, function ($n) use (&$token) {
            $token = $n->token;

            return true;
        });

        $this->get(route('password.reset', ['token' => $token, 'email' => $u->email]))->assertOk();
        $this->post(route('password.update'), [
            'token' => $token, 'email' => $u->email, 'password' => 'NuevaClave1', 'password_confirmation' => 'NuevaClave1',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('NuevaClave1', $u->fresh()->password));
    }

    public function test_token_invalido_es_rechazado(): void
    {
        $u = User::factory()->create();
        $this->post(route('password.update'), [
            'token' => 'falso', 'email' => $u->email, 'password' => 'NuevaClave1', 'password_confirmation' => 'NuevaClave1',
        ])->assertSessionHasErrors('email');
        $this->assertSame(Password::INVALID_TOKEN, Password::reset(['token' => 'x', 'email' => $u->email, 'password' => 'NuevaClave1', 'password_confirmation' => 'NuevaClave1'], fn () => null));
    }
}
