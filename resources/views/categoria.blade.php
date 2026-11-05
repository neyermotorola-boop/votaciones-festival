@extends('layouts.app')
@section('titulo', $categoria->nombre)
@section('contenido')
<nav aria-label="breadcrumb"><ol class="breadcrumb small">
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
    @if($categoria->edicion)<li class="breadcrumb-item"><a href="{{ route('edicion', $categoria->edicion) }}">{{ $categoria->edicion->nombre }}</a></li>@endif
    <li class="breadcrumb-item active">{{ $categoria->nombre }}</li>
</ol></nav>
<header class="page-head d-flex gap-3 align-items-center">
    <div class="cat-ico">{{ $categoria->inicial }}</div>
    <div>
        <h1 class="mb-1">{{ $categoria->nombre }}</h1>
        @if($categoria->descripcion)<p class="text-muted mb-0">{{ $categoria->descripcion }}</p>@endif
    </div>
</header>
<div class="section-title mb-3">Votaciones</div>
<div class="d-grid gap-3">
@forelse($votaciones as $v)
    <a href="{{ route('votacion', $v) }}" class="card-f cat-card p-4">
        <div class="d-flex justify-content-between align-items-center gap-3">
            <div class="d-flex gap-3 align-items-center">
                <div class="ico-tile"><i class="bi bi-check2-square"></i></div>
                <div>
                    <h3 class="h6 mb-1">{{ $v->titulo }}</h3>
                    <div class="meta">
                        <span><i class="bi bi-list-ul"></i>{{ $v->opciones_count }} opciones</span>
                        @if($v->cierre)<span><i class="bi bi-clock"></i>Cierra {{ $v->cierre->translatedFormat('d M Y, H:i') }}</span>@endif
                    </div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3">
                @include('partials.estado', ['votacion' => $v])
                <i class="bi bi-chevron-right arrow"></i>
            </div>
        </div>
    </a>
@empty
    <div class="card-f empty-state"><i class="bi bi-inbox"></i>No hay votaciones disponibles en esta categoría.</div>
@endforelse
</div>
@endsection
