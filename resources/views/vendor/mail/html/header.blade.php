@props(['url'])
{{-- Encabezado de los correos con el logo del Festival (versión de texto oscuro para fondo claro). --}}
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
<img src="{{ asset('img/logo-festival-oscuro.png') }}" class="logo" alt="Fundación Festival de la Leyenda Vallenata" style="height: 64px; max-height: 64px; width: auto;">
</a>
</td>
</tr>
