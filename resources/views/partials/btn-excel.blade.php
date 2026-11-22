{{-- Botón de exportación a Excel. Uso: @include('partials.btn-excel', ['url' => ..., 'texto' => opcional]) --}}
<a class="btn btn-soft-success {{ $clase ?? '' }}" href="{{ $url }}"><i class="bi bi-file-earmark-excel me-1"></i>{{ $texto ?? 'Exportar Excel' }}</a>
