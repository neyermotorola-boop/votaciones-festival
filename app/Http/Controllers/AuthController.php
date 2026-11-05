<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $datos = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'acepta_datos' => ['accepted'],
        ], [
            'acepta_datos.accepted' => 'Debes aceptar el tratamiento de datos personales.',
        ]);

        $user = User::create([
            'name' => $datos['name'],
            'email' => $datos['email'],
            'password' => $datos['password'],
            'rol' => 'votante',
        ]);

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

        if (! Auth::attempt($credenciales, $request->boolean('recordar'))) {
            RateLimiter::hit($clave, 300);

            return back()->withErrors(['email' => 'Correo o contraseña incorrectos.'])->onlyInput('email');
        }

        RateLimiter::clear($clave);
        $request->session()->regenerate();
        Bitacora::registrar('Inicio de sesión', $request->ip());

        return redirect()->intended(Auth::user()->esAdmin() ? route('admin.panel') : route('home'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
