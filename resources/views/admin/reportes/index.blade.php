@extends('layouts.admin')
@section('titulo', 'Reportes')
@section('contenido')
<header class="page-head">
    <span class="eyebrow mb-1">Informes</span>
    <h1>Reportes en Excel</h1>
    <p class="text-muted mb-0">Descarga los resultados de las votaciones por edición y categoría.</p>
</header>

<div class="row g-3">
    <div class="col-lg-7">
        <form class="card-f p-4 p-md-5" method="get" action="{{ route('admin.reportes.excel') }}">
            <div class="mb-3">
                <label class="form-label" for="edicion_id">Edición</label>
                <select id="edicion_id" name="edicion_id" class="form-select" required>
                    @foreach($ediciones as $e)
                        <option value="{{ $e->id }}" @selected($e->id == $activa)>{{ $e->nombre }} ({{ $e->anio }})</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label" for="categoria_id">Tipo de reporte (categoría)</label>
                <select id="categoria_id" name="categoria_id" class="form-select">
                    <option value="">Todas las categorías</option>
                    @foreach($ediciones as $e)
                        @foreach($e->categorias as $c)
                            <option value="{{ $c->id }}" data-edicion="{{ $e->id }}">{{ $c->nombre }} · {{ $c->votaciones_count }} votación(es)</option>
                        @endforeach
                    @endforeach
                </select>
                <div class="form-text">Elige, por ejemplo, solo Canciones o solo Baile, según lo que tengas cargado.</div>
            </div>
            <div class="form-check form-switch mb-4">
                <input class="form-check-input" type="checkbox" name="incluir_votos" value="1" id="iv" checked>
                <label class="form-check-label" for="iv">Incluir hoja con el detalle de cada voto (votante, correo y fecha)</label>
            </div>
            <button class="btn btn-brand btn-lg"><i class="bi bi-download me-2"></i>Descargar Excel</button>
        </form>
    </div>
    <div class="col-lg-5">
        <div class="card-f p-4 h-100">
            <div class="card-head"><h2>Contenido del archivo</h2><i class="bi bi-file-earmark-spreadsheet text-muted"></i></div>
            <div class="list-f">
                <div class="item"><div><b>Resumen</b><div class="small text-muted">Edición, categoría, totales y votantes únicos.</div></div></div>
                <div class="item"><div><b>Resultados</b><div class="small text-muted">Posición, votos y porcentaje por opción.</div></div></div>
                <div class="item"><div><b>Votaciones</b><div class="small text-muted">Estado, fechas y configuración de cada votación.</div></div></div>
                <div class="item"><div><b>Detalle de votos</b><div class="small text-muted">Un registro por voto emitido (opcional).</div></div></div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var ed = document.getElementById('edicion_id'), cat = document.getElementById('categoria_id');
    function filtrar() {
        Array.prototype.forEach.call(cat.options, function (o) {
            if (!o.dataset.edicion) return;
            var ok = o.dataset.edicion === ed.value;
            o.hidden = o.disabled = !ok;
            if (!ok && o.selected) cat.value = '';
        });
    }
    ed.addEventListener('change', filtrar);
    filtrar();
})();
</script>
@endsection
