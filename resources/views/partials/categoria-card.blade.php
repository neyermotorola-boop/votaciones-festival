<div class="col-12 col-md-6">
    <a href="{{ route('categoria', $c) }}" class="card-f cat-card p-4 h-100">
        <div class="d-flex gap-3 align-items-center">
            <div class="cat-ico">{{ $c->inicial }}</div>
            <div class="flex-grow-1 min-w-0">
                <h3 class="h5 mb-1">{{ $c->nombre }}</h3>
                <div class="text-muted small mb-2">{{ \Illuminate\Support\Str::limit($c->descripcion, 80) }}</div>
                @if($c->abiertas_count)
                    <span class="badge-estado est-abierta">{{ $c->abiertas_count }} votación(es) abierta(s)</span>
                @else
                    <span class="badge-estado est-cerrada">Sin votaciones abiertas</span>
                @endif
            </div>
            <i class="bi bi-chevron-right arrow fs-5"></i>
        </div>
    </a>
</div>
