<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bitacora;
use App\Models\Edicion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EdicionController extends Controller
{
    public function index()
    {
        return view('admin.ediciones.index', ['ediciones' => Edicion::withCount('categorias')->orderByDesc('anio')->get()]);
    }

    public function create()
    {
        return view('admin.ediciones.form', ['edicion' => new Edicion(['anio' => 2027, 'nombre' => 'Festival de la Leyenda Vallenata 2027'])]);
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);
        $this->guardar(new Edicion, $datos);
        Bitacora::registrar('Crear edición', $datos['nombre']);

        return redirect()->route('admin.ediciones.index')->with('ok', 'Edición creada.');
    }

    public function edit(Edicion $edicion)
    {
        return view('admin.ediciones.form', compact('edicion'));
    }

    public function update(Request $request, Edicion $edicion)
    {
        $datos = $this->validar($request, $edicion);
        $this->guardar($edicion, $datos);
        Bitacora::registrar('Editar edición', $edicion->nombre);

        return redirect()->route('admin.ediciones.index')->with('ok', 'Edición actualizada.');
    }

    public function destroy(Edicion $edicion)
    {
        if ($edicion->categorias()->exists()) {
            return back()->with('error', 'No se puede eliminar una edición con categorías.');
        }
        $edicion->delete();
        Bitacora::registrar('Eliminar edición', $edicion->nombre);

        return back()->with('ok', 'Edición eliminada.');
    }

    private function validar(Request $request, ?Edicion $edicion = null): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:160'],
            'anio' => ['required', 'integer', 'between:2000,2100', Rule::unique('ediciones', 'anio')->ignore($edicion?->id)],
            'descripcion' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function guardar(Edicion $edicion, array $datos): void
    {
        $edicion->fill($datos);
        $edicion->activa = request()->boolean('activa');
        $edicion->save();
        if ($edicion->activa) {
            // Solo una edición activa a la vez.
            Edicion::where('id', '!=', $edicion->id)->update(['activa' => false]);
        }
    }
}
