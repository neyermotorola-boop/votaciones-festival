@extends('layouts.auth')
@section('titulo', 'Recuperar contraseña')
@section('contenido')
<h1 class="auth-title">Recuperar contraseña</h1>
<p class="auth-sub">Ingresa el correo con el que te registraste y te enviaremos una contraseña temporal.</p>

<form method="post" action="{{ route('recuperar') }}" data-loading novalidate>
    @csrf
    <div class="mb-4">
        <label class="form-label" for="email">Correo electrónico</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}"
               class="form-control form-control-lg @error('email') is-invalid @enderror"
               placeholder="tu@correo.com" required autofocus autocomplete="email">
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <button type="submit" class="btn btn-brand btn-lg w-100" data-loading="Enviando…">Enviar contraseña temporal</button>
</form>

<div class="auth-divider"><span>¿Ya la recordaste?</span></div>
<a href="{{ route('login') }}" class="btn btn-glass btn-lg w-100">Volver a ingresar</a>
@endsection
