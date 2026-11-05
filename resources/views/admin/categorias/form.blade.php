@extends('layouts.admin')
@section('titulo', 'Categoría')
@section('contenido')
<header class="page-head">
    <a href="{{ route('admin.categorias.index') }}" class="small fw-semibold"><i class="bi bi-arrow-left me-1"></i>Categorías</a>
    <h1 class="mt-2">{{ $categoria->exists ? 'Editar' : 'Nueva' }} categoría</h1>
</header>
<form class="card-f p-4 p-md-5 form-section" method="post"
      action="{{ $categoria->exists ? route('admin.categorias.update', $categoria) : route('admin.categorias.store') }}">
    @csrf @if($categoria->exists) @method('PUT') @endif
    <div class="row g-3 mb-3">
        <div class="col-md-5"><label class="form-label" for="edicion_id">Edición</label>
            <select id="edicion_id" name="edicion_id" class="form-select" required>
                @foreach($ediciones as $e)<option value="{{ $e->id }}" @selected(old('edicion_id', $categoria->edicion_id) == $e->id)>{{ $e->nombre }}</option>@endforeach
            </select></div>
        <div class="col-md-7"><label class="form-label" for="nombre">Nombre</label>
            <input id="nombre" name="nombre" class="form-control" value="{{ old('nombre', $categoria->nombre) }}" required></div>
    </div>
    <div class="mb-3"><label class="form-label" for="descripcion">Descripción</label>
        <textarea id="descripcion" name="descripcion" rows="3" class="form-control">{{ old('descripcion', $categoria->descripcion) }}</textarea></div>
    <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" name="activa" value="1" id="act" @checked(old('activa', $categoria->activa))>
        <label class="form-check-label" for="act">Categoría activa</label></div>
    <div class="form-actions">
        <button class="btn btn-brand">Guardar</button>
        <a class="btn btn-ghost" href="{{ route('admin.categorias.index') }}">Cancelar</a>
    </div>
</form>
@endsection
