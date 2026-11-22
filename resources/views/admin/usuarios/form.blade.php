@extends('layouts.admin')
@section('titulo', 'Usuario')
@section('contenido')
@php($sel = old('permisos', $usuario->permisos ?? []))
@php($rolActual = old('rol', $usuario->rol ?: 'gestor'))
<header class="page-head">
    <a href="{{ route('admin.usuarios.index') }}" class="small fw-semibold"><i class="bi bi-arrow-left me-1"></i>Usuarios</a>
    <h1 class="mt-2">{{ $usuario->exists ? 'Editar' : 'Nuevo' }} usuario</h1>
</header>
<form class="card-f p-4 p-md-5 form-section" method="post"
      action="{{ $usuario->exists ? route('admin.usuarios.update', $usuario) : route('admin.usuarios.store') }}">
    @csrf @if($usuario->exists) @method('PUT') @endif
    <div class="row g-3 mb-3">
        <div class="col-md-6"><label class="form-label" for="name">Nombre</label>
            <input id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $usuario->name) }}" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-6"><label class="form-label" for="email">Correo</label>
            <input id="email" type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $usuario->email) }}" required>
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-4"><label class="form-label" for="tipo_documento">Tipo de documento</label>
            <select id="tipo_documento" name="tipo_documento" class="form-select @error('tipo_documento') is-invalid @enderror">
                <option value="">—</option>
                @foreach(\App\Models\User::TIPOS_DOCUMENTO as $k => $l)<option value="{{ $k }}" @selected(old('tipo_documento', $usuario->tipo_documento) === $k)>{{ $k }} · {{ $l }}</option>@endforeach
            </select>
            @error('tipo_documento')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-8"><label class="form-label" for="documento">Número de documento</label>
            <input id="documento" name="documento" maxlength="20" class="form-control @error('documento') is-invalid @enderror" value="{{ old('documento', $usuario->documento) }}">
            @error('documento')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div class="form-text">Obligatorio para votantes: una persona = una cuenta = un voto por categoría.</div></div>
        <div class="col-md-6"><label class="form-label" for="password">Contraseña {{ $usuario->exists ? '(déjala vacía para no cambiarla)' : '' }}</label>
            <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password" @required(! $usuario->exists)>
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div class="form-text">Mínimo 8 caracteres.</div></div>
        <div class="col-md-6"><label class="form-label" for="rol">Rol</label>
            <select id="rol" name="rol" class="form-select @error('rol') is-invalid @enderror">
                @foreach(\App\Models\User::ROLES as $k => $l)<option value="{{ $k }}" @selected($rolActual === $k)>{{ $l }}</option>@endforeach
            </select>
            @error('rol')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div class="form-text">Administrador: puede todo. Gestor: solo lo que marques abajo. Votante: solo vota.</div></div>
    </div>

    <fieldset id="bloque-permisos" class="mb-3">
        <legend class="form-label fw-semibold">Qué puede hacer este gestor</legend>
        @foreach(\App\Models\User::PERMISOS as $k => $l)
            <div class="form-check form-switch mb-1">
                <input class="form-check-input" type="checkbox" name="permisos[]" value="{{ $k }}" id="p_{{ $k }}" @checked(in_array($k, $sel, true))>
                <label class="form-check-label" for="p_{{ $k }}">{{ $l }}</label>
            </div>
        @endforeach
        @error('permisos.*')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        <div class="form-text">Un gestor sin ningún permiso no puede entrar al panel.</div>
    </fieldset>

    <div class="form-actions">
        <button class="btn btn-brand">Guardar</button>
        <a class="btn btn-ghost" href="{{ route('admin.usuarios.index') }}">Cancelar</a>
    </div>
</form>
<script>
    (function () {
        const rol = document.getElementById('rol'), bloque = document.getElementById('bloque-permisos');
        const mostrar = () => { bloque.style.display = rol.value === 'gestor' ? '' : 'none'; };
        rol.addEventListener('change', mostrar); mostrar();
    })();
</script>
@endsection
