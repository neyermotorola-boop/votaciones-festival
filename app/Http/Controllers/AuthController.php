<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

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

    public function recuperarForm()
    {
        return view('auth.recuperar');
    }

    /** Genera una contraseña temporal y la envía al correo registrado de la cuenta. */
    public function recuperar(Request $request)
    {
        $datos = $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'Ingresa tu correo electrónico.',
            'email.email' => 'El correo no tiene un formato válido.',
        ]);

        $user = User::where('email', $datos['email'])->where('activo', true)->first();

        if ($user) {
            $temporal = Str::password(10, symbols: false);

            try {
                Mail::raw(
                    "Hola {$user->name},\n\n".
                    'Solicitaste recuperar tu contraseña en '.config('app.name')."\n\n".
                    "Tu contraseña temporal es: {$temporal}\n\n".
                    "Ingresa con ella y no la compartas. Si no fuiste tú, ignora este mensaje.\n",
                    fn ($m) => $m->to($user->email)->subject('Recuperación de contraseña')
                );
                // Solo se cambia la clave si el correo pudo enviarse.
                $user->forceFill(['password' => $temporal])->save();
                Bitacora::registrar('Recuperación de contraseña', $request->ip());
            } catch (\Throwable $e) {
                Log::error('No se pudo enviar la contraseña temporal: '.$e->getMessage());
            }
        }

        // Respuesta idéntica exista o no la cuenta, para no revelar qué correos están registrados.
        return redirect()->route('login')->with('ok', 'Si el correo está registrado, te enviamos una contraseña temporal. Revisa tu bandeja de entrada.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
