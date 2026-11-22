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
@if($graficos)
<div class="row g-3 mb-3">
    <div class="col-lg-7"><div class="card-f p-4 h-100">
        <div class="card-head"><h2>Votos por día</h2><a href="{{ route('admin.graficos') }}" class="small fw-semibold">Ver todos los gráficos</a></div>
        <div style="position:relative;height:240px"><canvas id="pDias" aria-label="Votos por día"></canvas></div>
    </div></div>
    <div class="col-lg-5"><div class="card-f p-4 h-100">
        <div class="card-head"><h2>Votos por categoría</h2><i class="bi bi-pie-chart text-muted"></i></div>
        @if(array_sum($graficos['categorias']['data']) > 0)<div style="position:relative;height:240px"><canvas id="pCategorias" aria-label="Votos por categoría"></canvas></div>
        @else<div class="empty-state"><i class="bi bi-pie-chart"></i>Sin votos todavía.</div>@endif
    </div></div>
</div>
@endif
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
            <div class="card-head"><h2>Actividad reciente</h2>@if(auth()->user()->esAdmin())@include('partials.btn-excel', ['url' => route('admin.exportar.bitacora'), 'texto' => 'Bitácora', 'clase' => 'btn-sm'])@endif</div>
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
@if($graficos)
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
(function () {
    const g = @json($graficos);
    const colores = ['#5b2a86', '#e0a526', '#2b7a78', '#c8453b', '#3a6ea5', '#8a8d91'];
    const base = { responsive: true, maintainAspectRatio: false };
    new Chart(document.getElementById('pDias'), {
        type: 'line',
        data: { labels: g.dias.labels, datasets: [{ label: 'Votos', data: g.dias.data, borderColor: '#5b2a86', backgroundColor: 'rgba(91,42,134,.15)', fill: true, tension: .3 }] },
        options: { ...base, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });
    const c = document.getElementById('pCategorias');
    if (c) new Chart(c, { type: 'doughnut', data: { labels: g.categorias.labels, datasets: [{ data: g.categorias.data, backgroundColor: colores }] },
        options: { ...base, plugins: { legend: { position: 'bottom' } } } });
})();
</script>
@endpush
@endif
