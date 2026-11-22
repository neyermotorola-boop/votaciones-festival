@extends('layouts.app')
@section('titulo', 'Mis votos')
@section('contenido')
<header class="page-head">
    <span class="eyebrow mb-1">Participación</span>
    <h1>Mis votos</h1>
    <p class="text-muted mb-0">Categorías en las que ya votaste. Se permite un solo voto por categoría y edición.</p>
</header>

<div class="section-title mb-3">Categorías votadas ({{ $votos->count() }})</div>
@if($votos->isNotEmpty())
    <div class="card-f p-3 table-responsive mb-4">
        <table class="table table-f align-middle">
            <thead><tr><th>Edición</th><th>Categoría</th><th>Votación</th><th>Mi opción</th><th>Fecha</th></tr></thead>
            <tbody>
            @foreach($votos as $v)
                <tr>
                    <td>{{ $v->edicion->anio }}</td>
                    <td><b>{{ $v->categoria->nombre }}</b></td>
                    <td>{{ $v->votacion->titulo }}</td>
                    <td><span class="badge-estado est-abierta">{{ $v->opcion->nombre }}</span></td>
                    <td class="text-muted small">{{ $v->created_at->translatedFormat('d M Y, H:i') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@else
    <div class="card-f empty-state mb-4"><i class="bi bi-inbox"></i>Aún no has votado en ninguna categoría.</div>
@endif

<div class="section-title mb-3">Categorías pendientes por votar</div>
@if($pendientes->isNotEmpty())
    <div class="row g-3">
        @foreach($pendientes as $c)
            <div class="col-12 col-md-6">
                <a href="{{ route('categoria', $c) }}" class="card-f cat-card p-4 h-100">
                    <div class="d-flex gap-3 align-items-center">
                        <div class="cat-ico">{{ $c->inicial }}</div>
                        <div class="flex-grow-1"><h2 class="h5 mb-1">{{ $c->nombre }}</h2><span class="badge-estado est-abierta">Disponible para votar</span></div>
                        <i class="bi bi-chevron-right arrow fs-5"></i>
                    </div>
                </a>
            </div>
        @endforeach
    </div>
@else
    <div class="card-f empty-state"><i class="bi bi-check2-circle"></i>No tienes categorías pendientes en la edición activa.</div>
@endif
@endsection
