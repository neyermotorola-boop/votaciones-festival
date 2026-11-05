@extends('layouts.auth')
@section('titulo', 'Registro')
@section('contenido')
<h1 class="auth-title">Crea tu cuenta</h1>
<p class="auth-sub">Regístrate gratis para poder votar.</p>

<form method="post" action="{{ route('registro') }}" data-loading novalidate>
    @csrf
    <div class="mb-3">
        <label class="form-label" for="name">Nombre completo</label>
        <input id="name" name="name" value="{{ old('name') }}" class="form-control form-control-lg @error('name') is-invalid @enderror" required autofocus autocomplete="name">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
        <label class="form-label" for="email">Correo electrónico</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control form-control-lg @error('email') is-invalid @enderror" required autocomplete="email">
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
        <label class="form-label" for="password">Contraseña <span class="text-muted small">(mínimo 8 caracteres)</span></label>
        <div class="input-group input-group-lg has-validation">
            <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" required minlength="8" autocomplete="new-password">
            <button type="button" class="btn btn-toggle-pass" data-toggle-pass="password" aria-pressed="false">Mostrar</button>
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label" for="pc">Confirmar contraseña</label>
        <input id="pc" type="password" name="password_confirmation" class="form-control form-control-lg" required autocomplete="new-password">
    </div>
    <div class="form-check mb-4">
        <input class="form-check-input @error('acepta_datos') is-invalid @enderror" type="checkbox" name="acepta_datos" id="ad" value="1" required>
        <label class="form-check-label small" for="ad">Acepto el tratamiento de mis datos personales. <span class="pv">Texto legal pendiente de validación</span></label>
        @error('acepta_datos')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <button type="submit" class="btn btn-brand btn-lg w-100" data-loading="Creando cuenta…">Registrarme</button>
</form>

<div class="auth-divider"><span>¿Ya tienes cuenta?</span></div>
<a href="{{ route('login') }}" class="btn btn-glass btn-lg w-100">Iniciar sesión</a>
@endsection
