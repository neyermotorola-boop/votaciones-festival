@extends('layouts.app')
@section('titulo', $edicion->nombre)
@section('contenido')
<nav aria-label="breadcrumb"><ol class="breadcrumb small">
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
    <li class="breadcrumb-item"><a href="{{ route('ediciones') }}">Ediciones</a></li>
    <li class="breadcrumb-item active">{{ $edicion->anio }}</li>
</ol></nav>
<header class="page-head">
    <span class="eyebrow mb-1">Edición {{ $edicion->anio }}</span>
    <h1>{{ $edicion->nombre }}</h1>
    @if($edicion->descripcion)<p class="text-muted mb-0">{{ $edicion->descripcion }}</p>@endif
</header>
<div class="section-title mb-3">Categorías</div>
@if($categorias->isNotEmpty())
    <div class="row g-3">
        @foreach($categorias as $c)
            @include('partials.categoria-card')
        @endforeach
    </div>
@else
    <div class="card-f empty-state"><i class="bi bi-collection"></i>Esta edición aún no tiene categorías publicadas.</div>
@endif
@endsection
