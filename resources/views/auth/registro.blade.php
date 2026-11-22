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
    <div class="row g-2 mb-1">
        <div class="col-5">
            <label class="form-label" for="tipo_documento">Tipo de documento</label>
            <select id="tipo_documento" name="tipo_documento" class="form-select form-select-lg @error('tipo_documento') is-invalid @enderror" required>
                <option value="">Selecciona…</option>
                @foreach(\App\Models\User::TIPOS_DOCUMENTO as $k => $l)<option value="{{ $k }}" @selected(old('tipo_documento') === $k)>{{ $k }} · {{ $l }}</option>@endforeach
            </select>
            @error('tipo_documento')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-7">
            <label class="form-label" for="documento">Número de documento</label>
            <input id="documento" name="documento" value="{{ old('documento') }}" inputmode="text" maxlength="20" class="form-control form-control-lg @error('documento') is-invalid @enderror" required autocomplete="off">
            @error('documento')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <p class="small text-muted mb-3">Tu documento identifica tu voto: solo se permite <b>una cuenta por persona</b> y un voto por categoría. No podrás cambiarlo después.</p>
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
<script src="{{ asset('js/documento.js') }}?v={{ filemtime(public_path('js/documento.js')) }}" defer></script>
@endsection