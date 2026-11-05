@extends('layouts.app')
@section('titulo', $votacion->titulo)
@section('contenido')
<nav aria-label="breadcrumb"><ol class="breadcrumb small">
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
    <li class="breadcrumb-item"><a href="{{ route('categoria', $votacion->categoria) }}">{{ $votacion->categoria->nombre }}</a></li>
    <li class="breadcrumb-item active">{{ $votacion->titulo }}</li>
</ol></nav>

<div class="card-f p-4 p-md-5 mb-4">
    <div class="d-flex justify-content-between align-items-start gap-3 mb-2">
        <h1 class="h3 mb-0">{{ $votacion->titulo }}</h1>
        @include('partials.estado', ['votacion' => $votacion])
    </div>
    @if($votacion->descripcion)<p class="text-muted mb-3">{{ $votacion->descripcion }}</p>@endif
    <div class="meta">
        <span><i class="bi bi-person-check"></i>Votos por persona: <b class="text-body">{{ $votacion->votos_por_usuario }}</b></span>
        @if($votacion->cierre)<span><i class="bi bi-clock"></i>Cierre: <b class="text-body">{{ $votacion->cierre->translatedFormat('d M Y, H:i') }}</b></span>@endif
    </div>
</div>

@php $abierta = $votacion->estaAbierta(); $limite = $misVotos >= $votacion->votos_por_usuario; @endphp

@if($abierta)
    @guest
        <div class="card-f p-4 mb-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div class="d-flex gap-3 align-items-center">
                <div class="ico-tile gold"><i class="bi bi-lock"></i></div>
                <div><b>Para votar necesitas una cuenta</b><div class="small text-muted">Es gratis y toma menos de un minuto.</div></div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('login') }}" class="btn btn-soft">Ingresar</a>
                <a href="{{ route('registro') }}" class="btn btn-brand">Registrarme</a>
            </div>
        </div>
    @endguest
    @if(auth()->check() && $limite)
        <div class="alert alert-info"><i class="bi bi-patch-check-fill"></i><div>Ya usaste tus {{ $votacion->votos_por_usuario }} voto(s) en esta votación. Gracias por participar.</div></div>
    @endif
    <form method="post" action="{{ route('votacion.confirmar', $votacion) }}">
        @csrf
        <div class="section-title mb-3">Elige tu opción</div>
        <div class="d-grid gap-3 mb-4">
            @foreach($votacion->opciones as $o)
                @php $ya = in_array($o->id, $votadas); @endphp
                <div>
                    <input class="opt-input visually-hidden" type="radio" name="opcion_id" id="o{{ $o->id }}" value="{{ $o->id }}" required @disabled($limite || $ya || ! auth()->check())>
                    <label class="opt-card gap-3 align-items-center" for="o{{ $o->id }}">
                        <span class="dot"></span>
                        <span class="flex-grow-1"><b>{{ $o->nombre }}</b>
                            @if($o->descripcion)<br><small class="text-muted">{{ $o->descripcion }}</small>@endif</span>
                        @if($ya)<span class="badge-estado est-abierta">Tu voto</span>@endif
                    </label>
                </div>
            @endforeach
        </div>
        @if(auth()->check() && ! $limite)
            <button class="btn btn-brand btn-lg w-100">Continuar <i class="bi bi-arrow-right ms-1"></i></button>
        @endif
    </form>
@elseif($votacion->estadoEfectivo() === 'programada')
    <div class="alert alert-info"><i class="bi bi-calendar-event"></i><div>Esta votación abre el {{ $votacion->apertura->translatedFormat('d M Y, H:i') }}.</div></div>
@else
    <div class="alert alert-secondary"><i class="bi bi-slash-circle"></i><div>Esta votación no está recibiendo votos.</div></div>
@endif

@if($resultados)
    <div class="section-title mt-5 mb-3">Resultados</div>
    <div class="card-f p-4 p-md-5">
        @foreach($resultados as $r)
            <div class="{{ $loop->last ? '' : 'mb-4' }}">
                <div class="d-flex justify-content-between mb-2"><span class="fw-semibold">{{ $r->nombre }}</span><span><b>{{ $r->porcentaje }}%</b> <span class="text-muted small">· {{ $r->votos_count }} votos</span></span></div>
                <div class="bar-wrap"><div class="bar-fill {{ $loop->first ? 'top' : '' }}" style="width:{{ $r->porcentaje }}%"></div></div>
            </div>
        @endforeach
    </div>
@elseif($votacion->estadoEfectivo() === 'cerrada')
    <p class="text-muted mt-4 small">Los resultados serán publicados por la organización del Festival. <span class="pv">Pendiente de validación</span></p>
@endif
@endsection
