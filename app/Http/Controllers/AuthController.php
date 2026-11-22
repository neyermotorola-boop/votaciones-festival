<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use App\Models\User;
use App\Notifications\Bienvenida;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Throwable;

class AuthController extends Controller
{
    public function registroForm()
    {
        return view('auth.registro');
    }

    public function registro(Request $request)
    {
        $request->merge(['documento' => User::normalizarDocumento($request->input('documento'))]);

        $datos = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160', 'unique:users,email', User::reglaEmailCanonicoUnico()],
            ...User::reglasDocumento(),
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'acepta_datos' => ['accepted'],
        ], [
            'acepta_datos.accepted' => 'Debes aceptar el tratamiento de datos personales.',
            'email.unique' => 'Este correo ya tiene una cuenta registrada. Solo se permite una cuenta por persona.',
        ] + User::mensajesDocumento());

        try {
            $user = User::create([
                'name' => $datos['name'],
                'email' => $datos['email'],
                'tipo_documento' => $datos['tipo_documento'],
                'documento' => $datos['documento'],
                'password' => $datos['password'],
                'rol' => 'votante',
            ]);
        } catch (UniqueConstraintViolationException) {
            // Dos registros simultáneos con el mismo documento o correo: MySQL rechaza el segundo.
            return back()->withInput()->withErrors(['documento' => 'Ya existe una cuenta con ese documento o correo.']);
        }

        $user->notificarCorreo(new Bienvenida);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('home')->with('ok', '¡Cuenta creada! Ya puedes votar.');
    }

    public function loginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credenciales = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required' => 'Ingresa tu correo electrónico.',
            'email.email' => 'El correo no tiene un formato válido.',
            'password.required' => 'Ingresa tu contraseña.',
        ]);

        $clave = Str::lower($credenciales['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($clave, 5)) {
            $minutos = (int) ceil(RateLimiter::availableIn($clave) / 60);

            return back()->withErrors([
                'throttle' => "Demasiados intentos fallidos. Intenta de nuevo en {$minutos} minuto(s).",
            ])->onlyInput('email');
        }

        if (! Auth::attempt($credenciales + ['activo' => true], $request->boolean('recordar'))) {
            RateLimiter::hit($clave, 300);

            return back()->withErrors(['email' => 'Correo o contraseña incorrectos.'])->onlyInput('email');
        }

        RateLimiter::clear($clave);
        $request->session()->regenerate();
        Bitacora::registrar('Inicio de sesión', $request->ip());

        return redirect()->intended(Auth::user()->tieneAccesoPanel() ? route('admin.panel') : route('home'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    public function olvideForm()
    {
        return view('auth.olvide');
    }

    /** Envía el enlace de recuperación. Responde igual exista o no el correo (no revela cuentas). */
    public function olvide(Request $request)
    {
        $request->validate(['email' => ['required', 'email']], [
            'email.required' => 'Ingresa tu correo electrónico.',
            'email.email' => 'El correo no tiene un formato válido.',
        ]);

        try {
            Password::sendResetLink($request->only('email'));
        } catch (Throwable $e) {
            Log::warning('No se pudo enviar el enlace de recuperación', ['error' => $e->getMessage()]);
        }

        return back()->with('ok', 'Si el correo está registrado, te enviamos un enlace para restablecer la contraseña. Revisa también la carpeta de spam.');
    }

    public function restablecerForm(Request $request, string $token)
    {
        return view('auth.restablecer', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function restablecer(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        $estado = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
                event(new PasswordReset($user));
                Bitacora::registrar('Recuperación de contraseña', $user->email);
            }
        );

        if ($estado !== Password::PASSWORD_RESET) {
            return back()->withErrors(['email' => 'El enlace no es válido o ya venció. Solicita uno nuevo.'])->onlyInput('email');
        }

        return redirect()->route('login')->with('ok', 'Contraseña actualizada. Ya puedes ingresar.');
    }
}
