@extends('layouts.app')
@section('titulo', 'Confirmar voto')
@section('contenido')
<div class="narrow">
    <div class="stepper mb-3"><span><b>1</b>Opción</span><span class="on"><b>2</b>Confirmar</span></div>
    <div class="card-f p-4 p-md-5 text-center">
        <div class="result-ico ico-tile"><i class="bi bi-box-arrow-in-down"></i></div>
        <h1 class="h4">Confirma tu voto</h1>
        <p class="text-muted mb-1">{{ $votacion->titulo }}</p>
        <div class="choice-box my-4">
            <span class="section-title">Votarás por</span>
            <div class="h4 mb-0 mt-1">{{ $opcion->nombre }}</div>
        </div>
        <p class="small text-muted">Una vez emitido, tu voto no podrá modificarse. <span class="pv">Regla pendiente de validación</span></p>
        <form method="post" action="{{ route('votacion.votar', $votacion) }}" class="d-grid gap-2">
            @csrf
            <input type="hidden" name="opcion_id" value="{{ $opcion->id }}">
            <button class="btn btn-brand btn-lg">Confirmar mi voto</button>
            <a class="btn btn-ghost" href="{{ route('votacion', $votacion) }}">Cambiar opción</a>
        </form>
    </div>
</div>
@endsection
