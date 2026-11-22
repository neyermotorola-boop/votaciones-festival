@extends('layouts.admin')
@section('titulo', 'Usuarios y permisos')
@section('contenido')
<header class="page-head d-flex flex-wrap justify-content-between align-items-end gap-3">
    <div><h1>Usuarios y permisos</h1><p class="text-muted mb-0">Crea usuarios y decide qué puede hacer cada uno en el panel.</p></div>
    <a class="btn btn-brand" href="{{ route('admin.usuarios.create') }}"><i class="bi bi-person-plus me-1"></i>Nuevo usuario</a>
</header>
<form class="card-f p-3 mb-3 row g-2 align-items-end" method="get">
    <div class="col-md-5"><label class="form-label small mb-1" for="q">Buscar</label>
        <input id="q" name="q" class="form-control" value="{{ request('q') }}" placeholder="Nombre o correo"></div>
    <div class="col-md-3"><label class="form-label small mb-1" for="rol">Rol</label>
        <select id="rol" name="rol" class="form-select"><option value="">Todos</option>
            @foreach(\App\Models\User::ROLES as $k => $l)<option value="{{ $k }}" @selected(request('rol') === $k)>{{ $l }}</option>@endforeach
        </select></div>
    <div class="col-md-4"><button class="btn btn-soft"><i class="bi bi-search me-1"></i>Filtrar</button>
        <a class="btn btn-ghost" href="{{ route('admin.usuarios.index') }}">Limpiar</a></div>
</form>
<div class="card-f p-3 table-responsive">
<table class="table table-f align-middle">
    <thead><tr><th>Usuario</th><th>Rol</th><th>Permisos</th><th>Votos</th><th>Estado</th><th></th></tr></thead>
    <tbody>
    @forelse($usuarios as $u)
        <tr>
            <td><b>{{ $u->name }}</b><div class="small text-muted">{{ $u->email }}</div>
                <div class="small text-muted">{{ $u->documento ? $u->tipo_documento.' '.$u->documento : 'Sin documento' }}</div></td>
            <td>{{ \App\Models\User::ROLES[$u->rol] ?? $u->rol }}</td>
            <td class="small">
                @if($u->esAdmin()) <span class="text-muted">Todos</span>
                @elseif($u->esGestor())
                    @forelse($u->permisos ?? [] as $p)<span class="badge-perm me-1">{{ \App\Models\User::PERMISOS[$p] ?? $p }}</span>@empty<span class="text-danger">Sin permisos</span>@endforelse
                @else <span class="text-muted">Solo vota</span> @endif
            </td>
            <td>{{ $u->votos_count }}</td>
            <td>@if($u->estaActivo())<span class="badge-estado est-abierta">Activo</span>@else<span class="badge-estado est-cerrada">Desactivado</span>@endif</td>
            <td class="text-end text-nowrap">
                <a class="btn btn-sm btn-soft" href="{{ route('admin.usuarios.edit', $u) }}"><i class="bi bi-pencil me-1"></i>Editar</a>
                @if(! $u->is(auth()->user()))
                <form class="d-inline" method="post" action="{{ route('admin.usuarios.estado', $u) }}">@csrf
                    <button class="btn btn-sm {{ $u->estaActivo() ? 'btn-soft-danger' : 'btn-soft' }}">{{ $u->estaActivo() ? 'Desactivar' : 'Activar' }}</button></form>
                @endif
            </td>
        </tr>
    @empty
        <tr class="empty"><td colspan="6"><div class="empty-state"><i class="bi bi-people"></i>No hay usuarios con ese filtro.</div></td></tr>
    @endforelse
    </tbody>
</table>
{{ $usuarios->links() }}
</div>
@endsection
