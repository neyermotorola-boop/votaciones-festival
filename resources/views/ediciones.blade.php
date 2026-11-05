@extends('layouts.app')
@section('titulo', 'Ediciones')
@section('contenido')
<header class="page-head">
    <span class="eyebrow mb-1">Historial</span>
    <h1>Ediciones del Festival</h1>
    <p class="text-muted mb-0">Consulta las ediciones anuales y sus categorías.</p>
</header>
<div class="row g-3">
@forelse($ediciones as $e)
    <div class="col-12 col-md-6">
        <a href="{{ route('edicion', $e) }}" class="card-f cat-card p-4 h-100">
            <div class="d-flex gap-3 align-items-start">
                <div class="ico-tile {{ $e->activa ? 'gold' : '' }}"><i class="bi bi-calendar-event"></i></div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-1">
                        <span class="section-title">{{ $e->anio }}</span>
                        @if($e->activa)<span class="badge-estado est-abierta">Activa</span>@endif
                    </div>
                    <h2 class="h5 mb-1">{{ $e->nombre }}</h2>
                    <div class="small text-muted mb-2">{{ \Illuminate\Support\Str::limit($e->descripcion, 110) }}</div>
                    <span class="small fw-semibold text-muted"><i class="bi bi-collection me-1"></i>{{ $e->categorias_count }} categoría(s)</span>
                </div>
            </div>
        </a>
    </div>
@empty
    <div class="col-12"><div class="card-f empty-state"><i class="bi bi-calendar-x"></i>Aún no hay ediciones registradas.</div></div>
@endforelse
</div>
@endsection
