<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Edicion;
use App\Models\User;
use App\Models\Voto;
use Illuminate\Http\Request;

/** Consulta administrativa de los votos registrados en MySQL. */
class VotoController extends Controller
{
    public function index(Request $request)
    {
        $filtros = $request->validate([
            'edicion_id' => ['nullable', 'integer'],
            'categoria_id' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:120'],
        ]);

        $consulta = Voto::with(['user:id,name,email', 'edicion:id,anio', 'categoria:id,nombre', 'votacion:id,titulo', 'opcion:id,nombre'])
            ->when($filtros['edicion_id'] ?? null, fn ($q, $v) => $q->where('edicion_id', $v))
            ->when($filtros['categoria_id'] ?? null, fn ($q, $v) => $q->where('categoria_id', $v))
            ->when($filtros['q'] ?? null, fn ($q, $v) => $q->where(fn ($w) => $w
                ->whereHas('user', fn ($u) => $u->where('email', 'like', "%{$v}%")->orWhere('name', 'like', "%{$v}%"))
                ->orWhere('documento', 'like', '%'.(User::normalizarDocumento($v) ?? '@@').'%')))
            ->latest('id');

        return view('admin.votos.index', [
            'votos' => $consulta->paginate(25)->withQueryString(),
            'ediciones' => Edicion::orderByDesc('anio')->get(['id', 'anio', 'nombre']),
            'categorias' => Categoria::orderBy('nombre')->get(['id', 'nombre', 'edicion_id']),
            'filtros' => $filtros,
        ]);
    }
}
