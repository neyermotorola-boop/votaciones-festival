@extends('layouts.admin')
@section('titulo', 'Edición')
@section('contenido')
<header class="page-head">
    <a href="{{ route('admin.ediciones.index') }}" class="small fw-semibold"><i class="bi bi-arrow-left me-1"></i>Ediciones</a>
    <h1 class="mt-2">{{ $edicion->exists ? 'Editar' : 'Nueva' }} edición</h1>
</header>
<form class="card-f p-4 p-md-5 form-section" method="post"
      action="{{ $edicion->exists ? route('admin.ediciones.update', $edicion) : route('admin.ediciones.store') }}">
    @csrf @if($edicion->exists) @method('PUT') @endif
    <div class="row g-3 mb-3">
        <div class="col-md-8"><label class="form-label" for="nombre">Nombre</label>
            <input id="nombre" name="nombre" class="form-control" value="{{ old('nombre', $edicion->nombre) }}" required></div>
        <div class="col-md-4"><label class="form-label" for="anio">Año</label>
            <input id="anio" type="number" name="anio" class="form-control" value="{{ old('anio', $edicion->anio) }}" required></div>
    </div>
    <div class="mb-3"><label class="form-label" for="descripcion">Descripción</label>
        <textarea id="descripcion" name="descripcion" rows="3" class="form-control">{{ old('descripcion', $edicion->descripcion) }}</textarea></div>
    <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" name="activa" value="1" id="act" @checked(old('activa', $edicion->activa))>
        <label class="form-check-label" for="act">Edición activa (se muestra en el sitio público)</label></div>
    <div class="form-actions">
        <button class="btn btn-brand">Guardar</button>
        <a class="btn btn-ghost" href="{{ route('admin.ediciones.index') }}">Cancelar</a>
    </div>
</form>
@endsection
