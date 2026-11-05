<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicoController;
use App\Models\Bitacora;
use App\Models\Categoria;
use App\Models\Opcion;
use App\Models\Votacion;
use Illuminate\Http\Request;

class VotacionController extends Controller
{
    public function index()
    {
        $votaciones = Votacion::with('categoria.edicion')->withCount(['opciones', 'votos'])->latest()->get();

        return view('admin.votaciones.index', compact('votaciones'));
    }

    public function create()
    {
        return view('admin.votaciones.form', [
            'votacion' => new Votacion(['votos_por_usuario' => 1]),
            'categorias' => Categoria::with('edicion')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $votacion = Votacion::create($this->validar($request) + ['estado' => Votacion::BORRADOR]);
        Bitacora::registrar('Crear votación', $votacion->titulo);

        return redirect()->route('admin.votaciones.show', $votacion)->with('ok', 'Votación creada. Agrega al menos 2 opciones para abrirla.');
    }

    public function show(Votacion $votacion)
    {
        $votacion->load('categoria.edicion', 'opciones');
        $resultados = PublicoController::resultados($votacion);

        return view('admin.votaciones.show', compact('votacion', 'resultados'));
    }

    public function edit(Votacion $votacion)
    {
        return view('admin.votaciones.form', ['votacion' => $votacion, 'categorias' => Categoria::with('edicion')->get()]);
    }

    public function update(Request $request, Votacion $votacion)
    {
        $votacion->update($this->validar($request));
        Bitacora::registrar('Editar votación', $votacion->titulo);

        return redirect()->route('admin.votaciones.show', $votacion)->with('ok', 'Votación actualizada.');
    }

    public function destroy(Votacion $votacion)
    {
        if ($votacion->votos()->exists()) {
            return back()->with('error', 'No se puede eliminar una votación con votos; ciérrala.');
        }
        $votacion->delete();
        Bitacora::registrar('Eliminar votación', $votacion->titulo);

        return redirect()->route('admin.votaciones.index')->with('ok', 'Votación eliminada.');
    }

    public function estado(Request $request, Votacion $votacion)
    {
        $nuevo = $request->validate(['estado' => ['required', 'in:abierta,cerrada,borrador']])['estado'];

        if ($nuevo === Votacion::ABIERTA && $votacion->opciones()->count() < 2) {
            return back()->with('error', 'RN-04: una votación necesita al menos 2 opciones para abrirse.');
        }
        if ($nuevo === Votacion::BORRADOR && $votacion->votos()->exists()) {
            return back()->with('error', 'No se puede volver a borrador una votación con votos.');
        }

        $votacion->update(['estado' => $nuevo]);
        Bitacora::registrar('Cambiar estado a '.$nuevo, $votacion->titulo);

        return back()->with('ok', "Estado actualizado a «{$nuevo}».");
    }

    public function publicar(Votacion $votacion)
    {
        $votacion->update(['resultados_publicos' => ! $votacion->resultados_publicos]);
        Bitacora::registrar($votacion->resultados_publicos ? 'Publicar resultados' : 'Ocultar resultados', $votacion->titulo);

        return back()->with('ok', $votacion->resultados_publicos ? 'Resultados visibles al público.' : 'Resultados ocultos.');
    }

    public function opcionStore(Request $request, Votacion $votacion)
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:160'],
            'descripcion' => ['nullable', 'string', 'max:500'],
        ]);
        $votacion->opciones()->create($datos);
        Bitacora::registrar('Agregar opción', $datos['nombre']);

        return back()->with('ok', 'Opción agregada.');
    }

    public function opcionDestroy(Votacion $votacion, Opcion $opcion)
    {
        abort_unless($opcion->votacion_id === $votacion->id, 404);
        if ($opcion->votos()->exists()) {
            return back()->with('error', 'No se puede eliminar una opción que ya tiene votos.');
        }
        $opcion->delete();
        Bitacora::registrar('Eliminar opción', $opcion->nombre);

        return back()->with('ok', 'Opción eliminada.');
    }

    private function validar(Request $request): array
    {
        $datos = $request->validate([
            'categoria_id' => ['required', 'exists:categorias,id'],
            'titulo' => ['required', 'string', 'max:160'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'apertura' => ['nullable', 'date'],
            'cierre' => ['nullable', 'date', 'after:apertura'],
            'votos_por_usuario' => ['required', 'integer', 'between:1,20'],
        ]);
        $datos['resultados_publicos'] = $request->boolean('resultados_publicos');

        return $datos;
    }
}
