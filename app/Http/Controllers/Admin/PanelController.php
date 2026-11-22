<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bitacora;
use App\Models\Categoria;
use App\Models\Edicion;
use App\Models\User;
use App\Models\Votacion;
use App\Models\Voto;
use App\Services\EstadisticasVotos;

class PanelController extends Controller
{
    public function index()
    {
        $stats = [
            'ediciones' => Edicion::count(),
            'categorias' => Categoria::count(),
            'abiertas' => Votacion::where('estado', Votacion::ABIERTA)->count(),
            'votantes' => User::where('rol', 'votante')->count(),
            'votos' => Voto::count(),
        ];
        $recientes = Bitacora::with('usuario')->latest()->limit(8)->get();
        $topVotaciones = Votacion::withCount('votos')->orderByDesc('votos_count')->limit(5)->get();

        // Los gráficos solo se calculan y se muestran a quien tiene el permiso «resultados».
        $graficos = null;
        if (auth()->user()->puede('resultados')) {
            $edicionId = Edicion::activa()?->id;
            $graficos = ['dias' => EstadisticasVotos::porDia($edicionId), 'categorias' => EstadisticasVotos::porCategoria($edicionId)];
        }

        return view('admin.panel', compact('stats', 'recientes', 'topVotaciones', 'graficos'));
    }
}
