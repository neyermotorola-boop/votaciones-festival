<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bitacora;
use App\Models\Edicion;
use App\Models\Votacion;
use Illuminate\Support\Facades\DB;
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
        $anio = (Edicion::max('anio') ?? (int) date('Y') - 1) + 1;

        return view('admin.ediciones.form', [
            'edicion' => new Edicion(['anio' => $anio, 'nombre' => "Festival de la Leyenda Vallenata $anio"]),
            'anteriores' => Edicion::orderByDesc('anio')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);
        unset($datos['copiar_de']);
        $edicion = new Edicion;
        $this->guardar($edicion, $datos);
        Bitacora::registrar('Crear edición', $datos['nombre']);

        if ($origen = Edicion::find($request->input('copiar_de'))) {
            $this->copiarEstructura($origen, $edicion);
            Bitacora::registrar('Copiar estructura de edición', "{$origen->anio} → {$edicion->anio}");
        }

        return redirect()->route('admin.ediciones.index')->with('ok', 'Edición creada.');
    }

    public function edit(Edicion $edicion)
    {
        return view('admin.ediciones.form', ['edicion' => $edicion, 'anteriores' => collect()]);
    }

    public function update(Request $request, Edicion $edicion)
    {
        $datos = $this->validar($request, $edicion);
        unset($datos['copiar_de']);
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
            'copiar_de' => ['nullable', 'exists:ediciones,id'],
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

    /**
     * Copia categorías, votaciones y opciones de una edición anterior a la nueva.
     * Las votaciones copiadas quedan en borrador y sin votos ni fechas, para que el administrador las revise y abra.
     */
    private function copiarEstructura(Edicion $origen, Edicion $nueva): void
    {
        DB::transaction(function () use ($origen, $nueva) {
            foreach ($origen->categorias()->with('votaciones.opciones')->get() as $cat) {
                $copia = $nueva->categorias()->create(['nombre' => $cat->nombre, 'descripcion' => $cat->descripcion, 'activa' => $cat->activa]);
                foreach ($cat->votaciones as $v) {
                    $nv = $copia->votaciones()->create([
                        'titulo' => $v->titulo, 'descripcion' => $v->descripcion, 'estado' => Votacion::BORRADOR,
                        'votos_por_usuario' => $v->votos_por_usuario, 'resultados_publicos' => false,
                    ]);
                    foreach ($v->opciones as $o) {
                        $nv->opciones()->create(['nombre' => $o->nombre, 'descripcion' => $o->descripcion]);
                    }
                }
            }
        });
    }
}
