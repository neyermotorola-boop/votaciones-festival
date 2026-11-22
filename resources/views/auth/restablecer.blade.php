@extends('layouts.auth')
@section('titulo', 'Nueva contraseña')
@section('contenido')
<h1 class="auth-title">Crea una nueva contraseña</h1>
<p class="auth-sub">Debe tener al menos 8 caracteres.</p>

<form method="post" action="{{ route('password.update') }}" data-loading novalidate>
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <div class="mb-3">
        <label class="form-label" for="email">Correo electrónico</label>
        <input id="email" type="email" name="email" value="{{ old('email', $email) }}"
               class="form-control form-control-lg @error('email') is-invalid @enderror" required autocomplete="username">
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
        <label class="form-label" for="password">Nueva contraseña</label>
        <div class="input-group input-group-lg has-validation">
            <input id="password" type="password" name="password"
                   class="form-control @error('password') is-invalid @enderror" required autocomplete="new-password">
            <button type="button" class="btn btn-toggle-pass" data-toggle-pass="password" aria-pressed="false">Mostrar</button>
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="mb-4">
        <label class="form-label" for="password_confirmation">Confirmar contraseña</label>
        <input id="password_confirmation" type="password" name="password_confirmation"
               class="form-control form-control-lg" required autocomplete="new-password">
    </div>
    <button type="submit" class="btn btn-brand btn-lg w-100" data-loading="Guardando…">Guardar contraseña</button>
</form>
@endsection
