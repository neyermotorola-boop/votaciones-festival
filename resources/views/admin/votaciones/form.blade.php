@extends('layouts.admin')
@section('titulo', 'Votación')
@section('contenido')
<header class="page-head">
    <a href="{{ $votacion->exists ? route('admin.votaciones.show', $votacion) : route('admin.votaciones.index') }}" class="small fw-semibold"><i class="bi bi-arrow-left me-1"></i>Volver</a>
    <h1 class="mt-2">{{ $votacion->exists ? 'Editar' : 'Nueva' }} votación</h1>
</header>
<form class="card-f p-4 p-md-5 form-section" method="post"
      action="{{ $votacion->exists ? route('admin.votaciones.update', $votacion) : route('admin.votaciones.store') }}">
    @csrf @if($votacion->exists) @method('PUT') @endif
    <div class="mb-3"><label class="form-label" for="categoria_id">Categoría</label>
        <select id="categoria_id" name="categoria_id" class="form-select" required>
            @foreach($categorias as $c)<option value="{{ $c->id }}" @selected(old('categoria_id', $votacion->categoria_id) == $c->id)>{{ $c->edicion->anio }} · {{ $c->nombre }}</option>@endforeach
        </select></div>
    <div class="mb-3"><label class="form-label" for="titulo">Título</label>
        <input id="titulo" name="titulo" class="form-control" value="{{ old('titulo', $votacion->titulo) }}" required></div>
    <div class="mb-3"><label class="form-label" for="descripcion">Descripción</label>
        <textarea id="descripcion" name="descripcion" rows="3" class="form-control">{{ old('descripcion', $votacion->descripcion) }}</textarea></div>
    <div class="row g-3 mb-3">
        <div class="col-md-4"><label class="form-label" for="apertura">Apertura</label>
            <input id="apertura" type="datetime-local" name="apertura" class="form-control" value="{{ old('apertura', $votacion->apertura?->format('Y-m-d\TH:i')) }}"></div>
        <div class="col-md-4"><label class="form-label" for="cierre">Cierre</label>
            <input id="cierre" type="datetime-local" name="cierre" class="form-control" value="{{ old('cierre', $votacion->cierre?->format('Y-m-d\TH:i')) }}"></div>
        <div class="col-md-4"><label class="form-label" for="vpu">Votos por usuario</label>
            <input id="vpu" type="number" min="1" max="20" name="votos_por_usuario" class="form-control" value="{{ old('votos_por_usuario', $votacion->votos_por_usuario) }}" required></div>
    </div>
    <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" name="resultados_publicos" value="1" id="rp" @checked(old('resultados_publicos', $votacion->resultados_publicos))>
        <label class="form-check-label" for="rp">Mostrar resultados al público</label></div>
    <div class="form-actions">
        <button class="btn btn-brand">Guardar</button>
        <a class="btn btn-ghost" href="{{ $votacion->exists ? route('admin.votaciones.show', $votacion) : route('admin.votaciones.index') }}">Cancelar</a>
    </div>
</form>
@endsection
