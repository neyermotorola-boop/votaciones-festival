<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bitacora;
use App\Models\Categoria;
use App\Models\Edicion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoriaController extends Controller
{
    public function index()
    {
        $categorias = Categoria::with('edicion')->withCount('votaciones')->orderByDesc('edicion_id')->orderBy('nombre')->get();

        return view('admin.categorias.index', compact('categorias'));
    }

    public function create()
    {
        return view('admin.categorias.form', [
            'categoria' => new Categoria(['edicion_id' => Edicion::activa()?->id, 'activa' => true]),
            'ediciones' => Edicion::orderByDesc('anio')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);
        $categoria = Categoria::create($datos + ['activa' => $request->boolean('activa')]);
        Bitacora::registrar('Crear categoría', $categoria->nombre);

        return redirect()->route('admin.categorias.index')->with('ok', 'Categoría creada.');
    }

    public function edit(Categoria $categoria)
    {
        return view('admin.categorias.form', ['categoria' => $categoria, 'ediciones' => Edicion::orderByDesc('anio')->get()]);
    }

    public function update(Request $request, Categoria $categoria)
    {
        $datos = $this->validar($request, $categoria);
        $categoria->update($datos + ['activa' => $request->boolean('activa')]);
        Bitacora::registrar('Editar categoría', $categoria->nombre);

        return redirect()->route('admin.categorias.index')->with('ok', 'Categoría actualizada.');
    }

    public function destroy(Categoria $categoria)
    {
        // RN-09: no se elimina nada que ya tenga votos.
        if ($categoria->votaciones()->whereHas('votos')->exists()) {
            return back()->with('error', 'La categoría tiene votaciones con votos; desactívala en lugar de eliminarla.');
        }
        $categoria->delete();
        Bitacora::registrar('Eliminar categoría', $categoria->nombre);

        return back()->with('ok', 'Categoría eliminada.');
    }

    private function validar(Request $request, ?Categoria $categoria = null): array
    {
        return $request->validate([
            'edicion_id' => ['required', 'exists:ediciones,id'],
            'nombre' => ['required', 'string', 'max:120', Rule::unique('categorias')->where('edicion_id', $request->input('edicion_id'))->ignore($categoria?->id)],
            'descripcion' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
