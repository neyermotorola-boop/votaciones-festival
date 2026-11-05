@extends('layouts.auth')
@section('titulo', 'Ingresar')
@section('contenido')
<h1 class="auth-title">Bienvenido de nuevo</h1>
<p class="auth-sub">Ingresa con tu cuenta para participar en las votaciones.</p>

@if($errors->has('throttle'))
    <div class="alert alert-warning py-2" role="alert"><i class="bi bi-lock-fill"></i><div>{{ $errors->first('throttle') }}</div></div>
@endif

<form method="post" action="{{ route('login') }}" data-loading novalidate>
    @csrf
    <div class="mb-3">
        <label class="form-label" for="email">Correo electrónico</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}"
               class="form-control form-control-lg @error('email') is-invalid @enderror"
               placeholder="tu@correo.com" required autofocus autocomplete="username">
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
        <label class="form-label" for="password">Contraseña</label>
        <div class="input-group input-group-lg has-validation">
            <input id="password" type="password" name="password"
                   class="form-control @error('password') is-invalid @enderror"
                   placeholder="Tu contraseña" required autocomplete="current-password">
            <button type="button" class="btn btn-toggle-pass" data-toggle-pass="password" aria-pressed="false">Mostrar</button>
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="form-check mb-4">
        <input class="form-check-input" type="checkbox" name="recordar" id="rec" @checked(old('recordar'))>
        <label class="form-check-label" for="rec">Mantener sesión iniciada</label>
    </div>
    <button type="submit" class="btn btn-brand btn-lg w-100" data-loading="Ingresando…">Ingresar</button>
</form>

<div class="auth-divider"><span>¿Primera vez?</span></div>
<a href="{{ route('registro') }}" class="btn btn-glass btn-lg w-100">Crear una cuenta</a>
@endsection
