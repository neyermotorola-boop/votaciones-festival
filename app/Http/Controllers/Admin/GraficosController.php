<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Edicion;
use App\Models\Votacion;
use App\Services\EstadisticasVotos;
use Illuminate\Http\Request;

class GraficosController extends Controller
{
    public function index(Request $request)
    {
        $ediciones = Edicion::orderByDesc('anio')->get();
        $edicion = $request->filled('edicion_id') ? $ediciones->find($request->input('edicion_id')) : null;
        $edicion ??= Edicion::activa() ?? $ediciones->first();

        $votaciones = $edicion
            ? Votacion::with('categoria')->withCount('votos')
                ->whereHas('categoria', fn ($q) => $q->where('edicion_id', $edicion->id))->orderBy('id')->get()
            : collect();
        $votacion = $request->filled('votacion_id') ? $votaciones->firstWhere('id', (int) $request->input('votacion_id')) : null;
        $votacion ??= $votaciones->sortByDesc('votos_count')->first();

        $datos = [
            'resumen' => EstadisticasVotos::resumen($edicion?->id),
            'categorias' => EstadisticasVotos::porCategoria($edicion?->id),
            'dias' => EstadisticasVotos::porDia($edicion?->id),
            'opciones' => EstadisticasVotos::porOpcion($votacion),
        ];

        return view('admin.graficos.index', compact('ediciones', 'edicion', 'votaciones', 'votacion', 'datos'));
    }
}
