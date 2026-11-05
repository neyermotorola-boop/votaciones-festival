<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bitacora;
use App\Models\Categoria;
use App\Models\Edicion;
use App\Models\User;
use App\Models\Votacion;
use App\Models\Voto;

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

        return view('admin.panel', compact('stats', 'recientes', 'topVotaciones'));
    }
}
