@extends('layouts.admin')
@section('titulo', 'Gráficos de votación')
@section('contenido')
<header class="page-head">
    <span class="eyebrow mb-1">Resultados</span>
    <h1>Gráficos de votación</h1>
    <p class="text-muted mb-0">Datos reales leídos de MySQL en este momento.</p>
</header>

<form class="card-f p-3 mb-3 row g-2 align-items-end" method="get">
    <div class="col-md-4"><label class="form-label small mb-1" for="edicion_id">Edición</label>
        <select id="edicion_id" name="edicion_id" class="form-select" onchange="this.form.votacion_id.value='';this.form.submit()">
            @foreach($ediciones as $e)<option value="{{ $e->id }}" @selected($edicion?->id === $e->id)>{{ $e->nombre }}</option>@endforeach
        </select></div>
    <div class="col-md-6"><label class="form-label small mb-1" for="votacion_id">Votación (para el gráfico de opciones)</label>
        <select id="votacion_id" name="votacion_id" class="form-select" onchange="this.form.submit()">
            @foreach($votaciones as $v)<option value="{{ $v->id }}" @selected($votacion?->id === $v->id)>{{ $v->categoria->nombre }} · {{ $v->titulo }} ({{ $v->votos_count }} votos)</option>@endforeach
        </select></div>
    <div class="col-md-2"><button class="btn btn-soft w-100"><i class="bi bi-arrow-repeat me-1"></i>Actualizar</button></div>
</form>

<div class="row g-3 mb-3">
    @foreach([
        ['votos', 'Votos emitidos', 'bi-check2-all', 'red'],
        ['participantes', 'Personas que votaron', 'bi-person-check', 'green'],
        ['votantes', 'Votantes registrados', 'bi-people', 'gold'],
    ] as [$k, $l, $i, $c])
        <div class="col-6 col-lg-3"><div class="card-f stat"><div class="ico-tile {{ $c }}"><i class="bi {{ $i }}"></i></div>
            <div><div class="n">{{ $datos['resumen'][$k] }}</div><div class="l">{{ $l }}</div></div></div></div>
    @endforeach
    <div class="col-6 col-lg-3"><div class="card-f stat"><div class="ico-tile blue"><i class="bi bi-percent"></i></div>
        <div><div class="n">{{ $datos['resumen']['participacion'] }}%</div><div class="l">Participación</div></div></div></div>
</div>

<div class="row g-3">
    <div class="col-lg-7"><div class="card-f p-4 h-100">
        <div class="card-head"><h2>Votos por opción</h2><i class="bi bi-bar-chart-line text-muted"></i></div>
        <p class="small text-muted">{{ $votacion ? $votacion->categoria->nombre.' · '.$votacion->titulo : 'Sin votaciones en esta edición.' }}</p>
        @if(array_sum($datos['opciones']['data']) > 0)<div style="position:relative;height:300px"><canvas id="gOpciones" aria-label="Votos por opción"></canvas></div>
        @else<div class="empty-state"><i class="bi bi-bar-chart"></i>Aún no hay votos en esta votación.</div>@endif
    </div></div>
    <div class="col-lg-5"><div class="card-f p-4 h-100">
        <div class="card-head"><h2>Votos por categoría</h2><i class="bi bi-pie-chart text-muted"></i></div>
        @if(array_sum($datos['categorias']['data']) > 0)<div style="position:relative;height:300px"><canvas id="gCategorias" aria-label="Votos por categoría"></canvas></div>
        @else<div class="empty-state"><i class="bi bi-pie-chart"></i>Sin votos todavía.</div>@endif
    </div></div>
    <div class="col-12"><div class="card-f p-4">
        <div class="card-head"><h2>Votos por día (últimos 14 días)</h2><i class="bi bi-graph-up text-muted"></i></div>
        <div style="position:relative;height:260px"><canvas id="gDias" aria-label="Votos por día"></canvas></div>
    </div></div>
</div>
@endsection
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
(function () {
    const d = @json($datos);
    const colores = ['#5b2a86', '#e0a526', '#2b7a78', '#c8453b', '#3a6ea5', '#8a8d91', '#a0537e', '#6b8e23'];
    const base = { responsive: true, maintainAspectRatio: false };
    const el = id => document.getElementById(id);

    if (el('gOpciones')) new Chart(el('gOpciones'), {
        type: 'bar',
        data: { labels: d.opciones.labels, datasets: [{ label: 'Votos', data: d.opciones.data, backgroundColor: colores }] },
        options: { ...base, indexAxis: 'y', plugins: { legend: { display: false },
            tooltip: { callbacks: { label: c => c.parsed.x + ' votos (' + d.opciones.porcentajes[c.dataIndex] + '%)' } } },
            scales: { x: { beginAtZero: true, ticks: { precision: 0 } } } }
    });
    if (el('gCategorias')) new Chart(el('gCategorias'), {
        type: 'doughnut',
        data: { labels: d.categorias.labels, datasets: [{ data: d.categorias.data, backgroundColor: colores }] },
        options: { ...base, plugins: { legend: { position: 'bottom' } } }
    });
    new Chart(el('gDias'), {
        type: 'line',
        data: { labels: d.dias.labels, datasets: [{ label: 'Votos', data: d.dias.data, borderColor: '#5b2a86', backgroundColor: 'rgba(91,42,134,.15)', fill: true, tension: .3 }] },
        options: { ...base, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });
})();
</script>
@endpush
