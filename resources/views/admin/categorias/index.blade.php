@extends('layouts.admin')
@section('titulo', 'Categorías')
@section('contenido')
<header class="page-head d-flex flex-wrap justify-content-between align-items-end gap-3">
    <div><h1>Categorías</h1><p class="text-muted mb-0">Categorías vinculadas a cada edición.</p></div>
    <a class="btn btn-brand" href="{{ route('admin.categorias.create') }}"><i class="bi bi-plus-lg me-1"></i>Nueva categoría</a>
</header>
<div class="card-f p-3 table-responsive">
<table class="table table-f align-middle">
    <thead><tr><th>Nombre</th><th>Edición</th><th>Votaciones</th><th>Estado</th><th></th></tr></thead>
    <tbody>
    @forelse($categorias as $c)
        <tr>
            <td><div class="d-flex align-items-center gap-3"><span class="cat-ico sm">{{ $c->inicial }}</span><b>{{ $c->nombre }}</b></div></td>
            <td>{{ $c->edicion->anio }}</td><td>{{ $c->votaciones_count }}</td>
            <td>@if($c->activa)<span class="badge-estado est-abierta">Activa</span>@else<span class="badge-estado est-cerrada">Inactiva</span>@endif</td>
            <td class="text-end text-nowrap">
                <a class="btn btn-sm btn-soft" href="{{ route('admin.categorias.edit', $c) }}"><i class="bi bi-pencil me-1"></i>Editar</a>
                <form class="d-inline" method="post" action="{{ route('admin.categorias.destroy', $c) }}" onsubmit="return confirm('¿Eliminar esta categoría?')">@csrf @method('DELETE')
                    <button class="btn btn-sm btn-soft-danger" aria-label="Eliminar"><i class="bi bi-trash"></i></button></form>
            </td>
        </tr>
    @empty
        <tr class="empty"><td colspan="5"><div class="empty-state"><i class="bi bi-collection"></i>No hay categorías.</div></td></tr>
    @endforelse
    </tbody>
</table>
</div>
@endsection
