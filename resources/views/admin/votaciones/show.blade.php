@extends('layouts.admin')
@section('titulo', $votacion->titulo)
@section('contenido')
<header class="page-head">
    <a href="{{ route('admin.votaciones.index') }}" class="small fw-semibold"><i class="bi bi-arrow-left me-1"></i>Votaciones</a>
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mt-2">
        <div>
            <div class="section-title mb-1">{{ $votacion->categoria->edicion->anio }} · {{ $votacion->categoria->nombre }}</div>
            <h1 class="d-flex flex-wrap align-items-center gap-2 mb-0">{{ $votacion->titulo }} @include('partials.estado', ['votacion' => $votacion])</h1>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-soft" href="{{ route('admin.votaciones.edit', $votacion) }}"><i class="bi bi-pencil me-1"></i>Editar</a>
            @if($votacion->estado !== 'abierta')
                <form method="post" action="{{ route('admin.votaciones.estado', $votacion) }}">@csrf<input type="hidden" name="estado" value="abierta"><button class="btn btn-soft-success"><i class="bi bi-play-fill me-1"></i>Abrir</button></form>
            @else
                <form method="post" action="{{ route('admin.votaciones.estado', $votacion) }}" onsubmit="return confirm('¿Cerrar la votación?')">@csrf<input type="hidden" name="estado" value="cerrada"><button class="btn btn-soft-warning"><i class="bi bi-stop-fill me-1"></i>Cerrar</button></form>
            @endif
            <form method="post" action="{{ route('admin.votaciones.publicar', $votacion) }}">@csrf
                <button class="btn btn-soft"><i class="bi {{ $votacion->resultados_publicos ? 'bi-eye-slash' : 'bi-eye' }} me-1"></i>{{ $votacion->resultados_publicos ? 'Ocultar resultados' : 'Publicar resultados' }}</button></form>
            <form method="post" action="{{ route('admin.votaciones.destroy', $votacion) }}" onsubmit="return confirm('¿Eliminar votación?')">@csrf @method('DELETE')<button class="btn btn-soft-danger"><i class="bi bi-trash me-1"></i>Eliminar</button></form>
        </div>
    </div>
</header>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card-f p-4 h-100">
            <div class="card-head"><h2>Opciones participantes</h2><span class="count-pill">{{ $votacion->opciones->count() }}</span></div>
            <div class="list-f mb-4">
                @forelse($votacion->opciones as $o)
                    <div class="item">
                        <div><b>{{ $o->nombre }}</b>@if($o->descripcion)<div class="small text-muted">{{ $o->descripcion }}</div>@endif</div>
                        <form method="post" action="{{ route('admin.opciones.destroy', [$votacion, $o]) }}" onsubmit="return confirm('¿Eliminar opción?')">@csrf @method('DELETE')
                            <button class="btn btn-sm btn-soft-danger" aria-label="Eliminar opción"><i class="bi bi-x-lg"></i></button></form>
                    </div>
                @empty
                    <div class="empty-state"><i class="bi bi-list-ul"></i>Aún no hay opciones.</div>
                @endforelse
            </div>
            <form method="post" action="{{ route('admin.opciones.store', $votacion) }}">@csrf
                <div class="section-title mb-2">Agregar opción</div>
                <div class="d-grid gap-2">
                    <input name="nombre" class="form-control" placeholder="Nombre de la opción" required aria-label="Nombre de la opción">
                    <input name="descripcion" class="form-control" placeholder="Descripción (opcional)" aria-label="Descripción">
                    <button class="btn btn-brand"><i class="bi bi-plus-lg me-1"></i>Agregar opción</button>
                </div>
            </form>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card-f p-4 h-100">
            <div class="card-head"><h2>Resultados</h2><span class="small text-muted">Total: <b class="text-body">{{ $resultados->sum('votos_count') }}</b> votos</span></div>
            @forelse($resultados as $r)
                <div class="{{ $loop->last ? '' : 'mb-4' }}">
                    <div class="d-flex justify-content-between mb-2"><span class="fw-semibold">{{ $r->nombre }}</span><span><b>{{ $r->porcentaje }}%</b> <span class="text-muted small">· {{ $r->votos_count }}</span></span></div>
                    <div class="bar-wrap"><div class="bar-fill {{ $loop->first && $r->votos_count ? 'top' : '' }}" style="width:{{ $r->porcentaje }}%"></div></div>
                </div>
            @empty
                <div class="empty-state"><i class="bi bi-bar-chart"></i>Agrega opciones para ver resultados.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
