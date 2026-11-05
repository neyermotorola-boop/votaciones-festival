@php
    $e = $votacion->estadoEfectivo();
    $txt = ['abierta' => 'Abierta', 'cerrada' => 'Cerrada', 'borrador' => 'Borrador', 'programada' => 'Programada'][$e] ?? $e;
@endphp
<span class="badge-estado est-{{ $e }}">{{ $txt }}</span>
