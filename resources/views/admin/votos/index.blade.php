@extends('layouts.admin')
@section('titulo', 'Votos')
@section('contenido')
<header class="page-head d-flex flex-wrap justify-content-between align-items-end gap-3">
    <div><h1>Votos registrados</h1><p class="text-muted mb-0">Consulta administrativa de los votos guardados en MySQL.</p></div>
    <span class="count-pill fs-6">{{ $votos->total() }} voto(s)</span>
</header>

<form method="get" class="card-f p-3 mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-3"><label class="form-label small" for="edicion_id">Edición</label>
            <select id="edicion_id" name="edicion_id" class="form-select">
                <option value="">Todas</option>
                @foreach($ediciones as $e)<option value="{{ $e->id }}" @selected(($filtros['edicion_id'] ?? '') == $e->id)>{{ $e->anio }}</option>@endforeach
            </select></div>
        <div class="col-md-3"><label class="form-label small" for="categoria_id">Categoría</label>
            <select id="categoria_id" name="categoria_id" class="form-select">
                <option value="">Todas</option>
                @foreach($categorias as $c)<option value="{{ $c->id }}" @selected(($filtros['categoria_id'] ?? '') == $c->id)>{{ $c->nombre }}</option>@endforeach
            </select></div>
        <div class="col-md-4"><label class="form-label small" for="q">Votante (nombre, correo o documento)</label>
            <input id="q" name="q" class="form-control" value="{{ $filtros['q'] ?? '' }}" placeholder="Buscar..."></div>
        <div class="col-md-2 d-grid"><button class="btn btn-brand"><i class="bi bi-search me-1"></i>Filtrar</button></div>
    </div>
</form>

<div class="card-f p-3 table-responsive">
<table class="table table-f align-middle">
    <thead><tr><th>#</th><th>Fecha</th><th>Votante</th><th>Edición</th><th>Categoría</th><th>Votación</th><th>Opción elegida</th></tr></thead>
    <tbody>
    @forelse($votos as $v)
        <tr>
            <td class="text-muted">{{ $v->id }}</td>
            <td class="small text-nowrap">{{ $v->created_at->format('d/m/Y H:i') }}</td>
            <td><b>{{ $v->user->name }}</b><div class="small text-muted">{{ $v->user->email }}</div>
                <div class="small text-muted">{{ $v->documento ? 'Doc. '.$v->documento : 'Sin documento' }}</div></td>
            <td>{{ $v->edicion->anio }}</td>
            <td>{{ $v->categoria->nombre }}</td>
            <td>{{ $v->votacion->titulo }}</td>
            <td><span class="badge-estado est-abierta">{{ $v->opcion->nombre }}</span></td>
        </tr>
    @empty
        <tr class="empty"><td colspan="7"><div class="empty-state"><i class="bi bi-inbox"></i>No hay votos con esos filtros.</div></td></tr>
    @endforelse
    </tbody>
</table>
<div class="mt-2">{{ $votos->links() }}</div>
</div>
@endsection
