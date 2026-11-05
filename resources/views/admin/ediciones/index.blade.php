@extends('layouts.admin')
@section('titulo', 'Ediciones')
@section('contenido')
<header class="page-head d-flex flex-wrap justify-content-between align-items-end gap-3">
    <div><h1>Ediciones</h1><p class="text-muted mb-0">Ediciones anuales del Festival.</p></div>
    <a class="btn btn-brand" href="{{ route('admin.ediciones.create') }}"><i class="bi bi-plus-lg me-1"></i>Nueva edición</a>
</header>
<div class="card-f p-3 table-responsive">
<table class="table table-f align-middle">
    <thead><tr><th>Año</th><th>Nombre</th><th>Categorías</th><th>Estado</th><th></th></tr></thead>
    <tbody>
    @forelse($ediciones as $e)
        <tr>
            <td><b>{{ $e->anio }}</b></td><td>{{ $e->nombre }}</td><td>{{ $e->categorias_count }}</td>
            <td>@if($e->activa)<span class="badge-estado est-abierta">Activa</span>@else<span class="badge-estado est-cerrada">Inactiva</span>@endif</td>
            <td class="text-end text-nowrap">
                <a class="btn btn-sm btn-soft" href="{{ route('admin.ediciones.edit', $e) }}"><i class="bi bi-pencil me-1"></i>Editar</a>
                <form class="d-inline" method="post" action="{{ route('admin.ediciones.destroy', $e) }}" onsubmit="return confirm('¿Eliminar esta edición?')">@csrf @method('DELETE')
                    <button class="btn btn-sm btn-soft-danger" aria-label="Eliminar"><i class="bi bi-trash"></i></button></form>
            </td>
        </tr>
    @empty
        <tr class="empty"><td colspan="5"><div class="empty-state"><i class="bi bi-calendar-x"></i>No hay ediciones.</div></td></tr>
    @endforelse
    </tbody>
</table>
</div>
@endsection
