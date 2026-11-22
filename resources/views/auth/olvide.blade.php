@extends('layouts.auth')
@section('titulo', 'Recuperar contraseña')
@section('contenido')
<h1 class="auth-title">¿Olvidaste tu contraseña?</h1>
<p class="auth-sub">Escribe el correo de tu cuenta y te enviaremos un enlace para crear una nueva.</p>

<form method="post" action="{{ route('password.email') }}" data-loading novalidate>
    @csrf
    <div class="mb-4">
        <label class="form-label" for="email">Correo electrónico</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}"
               class="form-control form-control-lg @error('email') is-invalid @enderror"
               placeholder="tu@correo.com" required autofocus autocomplete="email">
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <button type="submit" class="btn btn-brand btn-lg w-100" data-loading="Enviando…">Enviar enlace</button>
</form>

<div class="auth-divider"><span>¿La recordaste?</span></div>
<a href="{{ route('login') }}" class="btn btn-glass btn-lg w-100">Volver a ingresar</a>
@endsection
