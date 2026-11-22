<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Votacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API GET /api/categorias/
 * Entrega, en JSON y desde MySQL, las categorías habilitadas con sus votaciones y opciones.
 */
class CategoriaApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate(['edicion_id' => ['nullable', 'integer']]);

        $categorias = Categoria::query()
            ->with([
                'edicion:id,nombre,anio,activa',
                'votaciones' => fn ($q) => $q->where('estado', '!=', Votacion::BORRADOR)->orderBy('id'),
                'votaciones.opciones' => fn ($q) => $q->orderBy('id'),
            ])
            ->where('activa', true)
            ->when($request->query('edicion_id'), fn ($q, $id) => $q->where('edicion_id', $id))
            ->orderByDesc('edicion_id')->orderBy('nombre')
            ->get();

        return response()->json([
            'total' => $categorias->count(),
            'data' => $categorias->map(fn (Categoria $c) => [
                'id' => $c->id,
                'nombre' => $c->nombre,
                'descripcion' => $c->descripcion,
                'edicion' => [
                    'id' => $c->edicion->id,
                    'nombre' => $c->edicion->nombre,
                    'anio' => $c->edicion->anio,
                    'activa' => $c->edicion->activa,
                ],
                'votaciones' => $c->votaciones->map(fn (Votacion $v) => [
                    'id' => $v->id,
                    'titulo' => $v->titulo,
                    'estado' => $v->estadoEfectivo(),
                    'opciones' => $v->opciones->map(fn ($o) => [
                        'id' => $o->id,
                        'nombre' => $o->nombre,
                        'descripcion' => $o->descripcion,
                    ])->values(),
                ])->values(),
            ])->values(),
        ], options: JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}
