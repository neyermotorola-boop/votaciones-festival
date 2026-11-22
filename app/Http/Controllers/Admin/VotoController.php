<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Edicion;
use App\Models\User;
use App\Models\Voto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/** Consulta administrativa de los votos registrados en MySQL. */
class VotoController extends Controller
{
    public function index(Request $request)
    {
        $filtros = self::filtros($request);

        return view('admin.votos.index', [
            'votos' => self::consulta($filtros)->latest('id')->paginate(25)->withQueryString(),
            'ediciones' => Edicion::orderByDesc('anio')->get(['id', 'anio', 'nombre']),
            'categorias' => Categoria::orderBy('nombre')->get(['id', 'nombre', 'edicion_id']),
            'filtros' => $filtros,
        ]);
    }

    public static function filtros(Request $request): array
    {
        return $request->validate([
            'edicion_id' => ['nullable', 'integer'],
            'categoria_id' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:120'],
        ]);
    }

    /** Consulta compartida por el listado y la exportación a Excel (mismos filtros). */
    public static function consulta(array $filtros): Builder
    {
        return Voto::with(['user:id,name,email', 'edicion:id,anio', 'categoria:id,nombre', 'votacion:id,titulo', 'opcion:id,nombre,imagen'])
            ->when($filtros['edicion_id'] ?? null, fn ($q, $v) => $q->where('edicion_id', $v))
            ->when($filtros['categoria_id'] ?? null, fn ($q, $v) => $q->where('categoria_id', $v))
            ->when($filtros['q'] ?? null, fn ($q, $v) => $q->where(fn ($w) => $w
                ->whereHas('user', fn ($u) => $u->where('email', 'like', "%{$v}%")->orWhere('name', 'like', "%{$v}%"))
                ->orWhere('documento', 'like', '%'.(User::normalizarDocumento($v) ?? '@@').'%')));
    }
}
