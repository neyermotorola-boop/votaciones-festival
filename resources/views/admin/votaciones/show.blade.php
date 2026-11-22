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
            @include('partials.btn-excel', ['url' => route('admin.exportar.opciones', $votacion), 'texto' => 'Opciones'])
            @if(auth()->user()->puede('resultados'))
                @include('partials.btn-excel', ['url' => route('admin.exportar.votos', ['categoria_id' => $votacion->categoria_id]), 'texto' => 'Votos'])
            @endif
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
            @php $conFoto = $votacion->categoria->opciones_con_imagen; $formatos = '.'.implode(',.', config('festival.imagen_formatos')); @endphp
            <div class="list-f mb-4">
                @forelse($votacion->opciones as $o)
                    <div class="item flex-wrap">
                        <div class="d-flex align-items-center gap-3 min-w-0">
                            @if($o->imagen_url)
                                <img src="{{ $o->imagen_url }}" alt="Foto de {{ $o->nombre }}" class="opt-thumb" loading="lazy">
                            @elseif($conFoto)
                                <span class="opt-thumb opt-thumb-empty" title="Sin foto">{{ $o->inicial }}</span>
                            @endif
                            <div class="min-w-0"><b>{{ $o->nombre }}</b>@if($o->descripcion)<div class="small text-muted">{{ $o->descripcion }}</div>@endif
                                @if($conFoto && ! $o->imagen)<div class="small text-danger"><i class="bi bi-exclamation-circle me-1"></i>Falta la foto</div>@endif</div>
                        </div>
                        <div class="d-flex gap-1">
                            <button class="btn btn-sm btn-soft" type="button" data-bs-toggle="collapse" data-bs-target="#edit-o{{ $o->id }}" aria-expanded="false" aria-label="Editar opción"><i class="bi bi-pencil"></i></button>
                            <form method="post" action="{{ route('admin.opciones.destroy', [$votacion, $o]) }}" onsubmit="return confirm('¿Eliminar opción?')">@csrf @method('DELETE')
                                <button class="btn btn-sm btn-soft-danger" aria-label="Eliminar opción"><i class="bi bi-x-lg"></i></button></form>
                        </div>
                        <form id="edit-o{{ $o->id }}" class="collapse w-100 mt-3" method="post" enctype="multipart/form-data" action="{{ route('admin.opciones.update', [$votacion, $o]) }}">
                            @csrf @method('PUT')
                            <div class="d-grid gap-2">
                                <input name="nombre" class="form-control" value="{{ $o->nombre }}" required aria-label="Nombre">
                                <input name="descripcion" class="form-control" value="{{ $o->descripcion }}" placeholder="Descripción (opcional)" aria-label="Descripción">
                                <label class="form-label mb-0 small" for="img-o{{ $o->id }}">{{ $o->imagen ? 'Reemplazar foto' : 'Agregar foto' }} <span class="text-muted fw-normal">(JPG, PNG o WEBP, máx. {{ round(config('festival.imagen_max_kb') / 1024, 1) }} MB)</span></label>
                                <input id="img-o{{ $o->id }}" type="file" name="imagen" accept="{{ $formatos }}" class="form-control">
                                @if($o->imagen)
                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="quitar_imagen" value="1" id="qi{{ $o->id }}">
                                        <label class="form-check-label small" for="qi{{ $o->id }}">Quitar la foto actual</label></div>
                                @endif
                                <button class="btn btn-sm btn-brand">Guardar cambios</button>
                            </div>
                        </form>
                    </div>
                @empty
                    <div class="empty-state"><i class="bi bi-list-ul"></i>Aún no hay opciones.</div>
                @endforelse
            </div>
            <form method="post" action="{{ route('admin.opciones.store', $votacion) }}" enctype="multipart/form-data">@csrf
                <div class="section-title mb-2">Agregar opción</div>
                <div class="d-grid gap-2">
                    <input name="nombre" class="form-control" placeholder="Nombre de la opción" value="{{ old('nombre') }}" required aria-label="Nombre de la opción">
                    <input name="descripcion" class="form-control" placeholder="Descripción (opcional)" value="{{ old('descripcion') }}" aria-label="Descripción">
                    <label class="form-label mb-0 small" for="imagen">
                        Foto del participante {{ $conFoto ? '(obligatoria en esta categoría)' : '(opcional)' }}
                        <span class="text-muted fw-normal">· JPG, PNG o WEBP, máx. {{ round(config('festival.imagen_max_kb') / 1024, 1) }} MB</span>
                    </label>
                    <input id="imagen" type="file" name="imagen" accept="{{ $formatos }}" class="form-control" @required($conFoto) data-preview="prev-nueva">
                    <img id="prev-nueva" class="opt-thumb lg" alt="Vista previa" hidden>
                    <button class="btn btn-brand"><i class="bi bi-plus-lg me-1"></i>Agregar opción</button>
                </div>
                @unless($conFoto)
                    <div class="form-text">Esta categoría muestra los participantes solo con texto. Para exigir foto, actívalo en <a href="{{ route('admin.categorias.edit', $votacion->categoria) }}">la categoría</a>.</div>
                @endunless
            </form>
            <script>
            document.querySelectorAll('input[data-preview]').forEach(function (inp) {
                inp.addEventListener('change', function () {
                    var img = document.getElementById(inp.dataset.preview), f = inp.files[0];
                    if (!f) { img.hidden = true; return; }
                    img.src = URL.createObjectURL(f); img.hidden = false;
                });
            });
            </script>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card-f p-4 h-100">
            <div class="card-head"><h2>Resultados</h2><span class="small text-muted">Total: <b class="text-body">{{ $resultados->sum('votos_count') }}</b> votos</span></div>
            @forelse($resultados as $r)
                <div class="{{ $loop->last ? '' : 'mb-4' }}">
                    <div class="d-flex justify-content-between mb-2"><span class="fw-semibold d-flex align-items-center gap-2">@if($r->imagen_url)<img src="{{ $r->imagen_url }}" alt="" class="opt-thumb sm" loading="lazy">@endif{{ $r->nombre }}</span><span><b>{{ $r->porcentaje }}%</b> <span class="text-muted small">· {{ $r->votos_count }}</span></span></div>
                    <div class="bar-wrap"><div class="bar-fill {{ $loop->first && $r->votos_count ? 'top' : '' }}" style="width:{{ $r->porcentaje }}%"></div></div>
                </div>
            @empty
                <div class="empty-state"><i class="bi bi-bar-chart"></i>Agrega opciones para ver resultados.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
