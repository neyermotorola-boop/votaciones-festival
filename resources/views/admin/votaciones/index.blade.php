@extends('layouts.admin')
@section('titulo', 'Votaciones')
@section('contenido')
<header class="page-head d-flex flex-wrap justify-content-between align-items-end gap-3">
    <div><h1>Votaciones</h1><p class="text-muted mb-0">Votaciones por categoría y sus opciones participantes.</p></div>
    <a class="btn btn-brand" href="{{ route('admin.votaciones.create') }}"><i class="bi bi-plus-lg me-1"></i>Nueva votación</a>
</header>
<div class="card-f p-3 table-responsive">
<table class="table table-f align-middle">
    <thead><tr><th>Título</th><th>Categoría</th><th>Opciones</th><th>Votos</th><th>Estado</th><th></th></tr></thead>
    <tbody>
    @forelse($votaciones as $v)
        <tr>
            <td><b>{{ $v->titulo }}</b></td>
            <td>{{ $v->categoria->nombre }} <span class="text-muted small">· {{ $v->categoria->edicion->anio }}</span></td>
            <td>{{ $v->opciones_count }}</td><td>{{ $v->votos_count }}</td>
            <td>@include('partials.estado', ['votacion' => $v])</td>
            <td class="text-end"><a class="btn btn-sm btn-soft" href="{{ route('admin.votaciones.show', $v) }}">Gestionar <i class="bi bi-chevron-right"></i></a></td>
        </tr>
    @empty
        <tr class="empty"><td colspan="6"><div class="empty-state"><i class="bi bi-inbox"></i>No hay votaciones.</div></td></tr>
    @endforelse
    </tbody>
</table>
</div>
@endsection
