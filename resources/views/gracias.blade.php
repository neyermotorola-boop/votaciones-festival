@extends('layouts.app')
@section('titulo', 'Voto registrado')
@section('contenido')
<div class="narrow card-f p-4 p-md-5 text-center">
    <div class="result-ico ico-tile green"><i class="bi bi-check-lg"></i></div>
    <h1 class="h3">Voto registrado</h1>
    <p class="text-muted">Gracias por participar en «{{ $votacion->titulo }}».</p>
    <div class="d-grid gap-2 mt-4">
        <a class="btn btn-brand" href="{{ route('votacion', $votacion) }}">Volver a la votación</a>
        <a class="btn btn-ghost" href="{{ route('home') }}">Ver otras categorías</a>
    </div>
</div>
@endsection
