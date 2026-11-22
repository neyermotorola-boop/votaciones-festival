@extends('layouts.app')
@section('titulo', 'Registra tu documento')
@section('contenido')
<div class="narrow card-f p-4 p-md-5">
    <h1 class="h3">Registra tu documento para votar</h1>
    <p class="text-muted">Para garantizar <b>un voto por persona</b>, cada cuenta debe estar ligada a un documento de identidad.
        Solo se permite una cuenta por documento y no podrás cambiarlo después.</p>
    <form method="post" action="{{ route('documento.guardar') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label" for="tipo_documento">Tipo de documento</label>
            <select id="tipo_documento" name="tipo_documento" class="form-select @error('tipo_documento') is-invalid @enderror" required>
                <option value="">Selecciona…</option>
                @foreach(\App\Models\User::TIPOS_DOCUMENTO as $k => $l)<option value="{{ $k }}" @selected(old('tipo_documento') === $k)>{{ $k }} · {{ $l }}</option>@endforeach
            </select>
            @error('tipo_documento')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-4">
            <label class="form-label" for="documento">Número de documento</label>
            <input id="documento" name="documento" value="{{ old('documento') }}" maxlength="20" class="form-control @error('documento') is-invalid @enderror" required autocomplete="off">
            @error('documento')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <button class="btn btn-brand w-100">Guardar documento</button>
    </form>
</div>
<script src="{{ asset('js/documento.js') }}?v={{ filemtime(public_path('js/documento.js')) }}" defer></script>
@endsection