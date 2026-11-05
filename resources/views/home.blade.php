@extends('layouts.app')
@section('titulo', 'Inicio')
@section('contenido')
<section class="hero mb-4" style="background-image:url('{{ asset('img/vallenato.png') }}')">
    <div>
        <span class="eyebrow mb-2">Edición {{ $edicion?->anio ?? '2027' }}</span>
        <h1>{{ $edicion?->nombre ?? 'Festival de la Leyenda Vallenata' }}</h1>
        <p class="lead mb-4">
            {{ $edicion?->descripcion ?: 'Participa en las votaciones del Festival. Tu voz también hace parte de la leyenda.' }}
        </p>
        @guest
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('registro') }}" class="btn btn-gold btn-lg">Crear cuenta para votar</a>
                <a href="{{ route('login') }}" class="btn btn-glass btn-lg">Ya tengo cuenta</a>
            </div>
        @else
            <a href="#categorias" class="btn btn-gold btn-lg">Ver categorías <i class="bi bi-arrow-down ms-1"></i></a>
        @endguest
    </div>
</section>

<div class="stepper mb-5">
    <span class="on"><b>1</b>Regístrate</span><span><b>2</b>Elige categoría</span><span><b>3</b>Escoge tu opción</span><span><b>4</b>Confirma</span>
</div>

<div class="d-flex justify-content-between align-items-end mb-3" id="categorias">
    <div>
        <div class="section-title mb-1">Categorías</div>
        <h2 class="h4 mb-0">Elige dónde votar</h2>
    </div>
    @if($edicion)<a href="{{ route('edicion', $edicion) }}" class="small fw-semibold">Ver edición <i class="bi bi-arrow-right"></i></a>@endif
</div>
@if($categorias->isNotEmpty())
    <div class="row g-3">
        @foreach($categorias as $c)
            @include('partials.categoria-card')
        @endforeach
    </div>
@else
    <div class="card-f empty-state"><i class="bi bi-collection"></i>Aún no hay categorías publicadas. Vuelve pronto.</div>
@endif
@endsection
