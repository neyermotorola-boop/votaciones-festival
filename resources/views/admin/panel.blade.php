@extends('layouts.admin')
@section('titulo', 'Panel')
@section('contenido')
<header class="page-head">
    <span class="eyebrow mb-1">Resumen</span>
    <h1>Panel de administración</h1>
    <p class="text-muted mb-0">Estado general de las votaciones del Festival.</p>
</header>
<div class="row g-3 mb-4">
    @foreach([
        ['ediciones', 'Ediciones', 'bi-calendar-event', ''],
        ['categorias', 'Categorías', 'bi-collection', 'blue'],
        ['abiertas', 'Votaciones abiertas', 'bi-broadcast', 'green'],
        ['votantes', 'Votantes', 'bi-people', 'gold'],
        ['votos', 'Votos emitidos', 'bi-check2-all', 'red'],
    ] as [$k, $l, $i, $c])
        <div class="col-6 col-lg">
            <div class="card-f stat">
                <div class="ico-tile {{ $c }}"><i class="bi {{ $i }}"></i></div>
                <div><div class="n">{{ $stats[$k] }}</div><div class="l">{{ $l }}</div></div>
            </div>
        </div>
    @endforeach
</div>
<div class="row g-3">
    <div class="col-lg-6">
        <div class="card-f p-4 h-100">
            <div class="card-head"><h2>Votaciones con más votos</h2><i class="bi bi-trophy text-muted"></i></div>
            <div class="list-f">
                @forelse($topVotaciones as $v)
                    <a href="{{ route('admin.votaciones.show', $v) }}" class="item">
                        <span class="fw-medium">{{ $v->titulo }}</span><span class="count-pill">{{ $v->votos_count }}</span></a>
                @empty
                    <div class="empty-state"><i class="bi bi-bar-chart"></i>Sin datos todavía.</div>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card-f p-4 h-100">
            <div class="card-head"><h2>Actividad reciente</h2><i class="bi bi-clock-history text-muted"></i></div>
            <div class="list-f">
                @forelse($recientes as $b)
                    <div class="item small">
                        <div><b>{{ $b->accion }}</b> <span class="text-muted">· {{ $b->detalle }}</span>
                            <div class="text-muted">{{ $b->usuario?->name }}</div></div>
                        <span class="text-muted text-nowrap">{{ $b->created_at->diffForHumans() }}</span>
                    </div>
                @empty
                    <div class="empty-state"><i class="bi bi-journal"></i>Sin actividad todavía.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
