<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicoController;
use App\Models\Bitacora;
use App\Models\Categoria;
use App\Models\Opcion;
use App\Models\Votacion;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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
        $datos = $this->validar($request);
        if ((int) $datos['categoria_id'] !== $votacion->categoria_id && $votacion->votos()->exists()) {
            return back()->withInput()->with('error', 'No se puede cambiar de categoría una votación que ya tiene votos.');
        }

        $votacion->update($datos);
        Bitacora::registrar('Editar votación', $votacion->titulo);

        return redirect()->route('admin.votaciones.show', $votacion)->with('ok', 'Votación actualizada.');
    }

    public function destroy(Votacion $votacion)
    {
        if ($votacion->votos()->exists()) {
            return back()->with('error', 'No se puede eliminar una votación con votos; ciérrala.');
        }
        $votacion->opciones->each->borrarImagen();
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
        $conFoto = (bool) $votacion->categoria->opciones_con_imagen;
        $datos = $this->validarOpcion($request, $conFoto ? 'required' : 'nullable');

        $opcion = $votacion->opciones()->make($datos);
        if ($request->hasFile('imagen')) {
            $opcion->imagen = $this->guardarImagen($request);
        }
        $opcion->save();
        Bitacora::registrar('Agregar opción', $datos['nombre']);

        return back()->with('ok', 'Opción agregada.');
    }

    public function opcionUpdate(Request $request, Votacion $votacion, Opcion $opcion)
    {
        abort_unless($opcion->votacion_id === $votacion->id, 404);
        $datos = $this->validarOpcion($request, 'nullable');

        // Con votos no se cambia el nombre (evita confundir a quien ya votó); la foto y la descripción sí.
        if ($opcion->votos()->exists() && $datos['nombre'] !== $opcion->nombre) {
            return back()->with('error', 'Esta opción ya tiene votos: no se puede cambiar el nombre.');
        }

        $opcion->fill($datos);
        if ($request->hasFile('imagen')) {
            $opcion->borrarImagen();
            $opcion->imagen = $this->guardarImagen($request);
        } elseif ($request->boolean('quitar_imagen')) {
            $opcion->borrarImagen();
            $opcion->imagen = null;
        }
        $opcion->save();
        Bitacora::registrar('Editar opción', $opcion->nombre);

        return back()->with('ok', 'Opción actualizada.');
    }

    public function opcionDestroy(Votacion $votacion, Opcion $opcion)
    {
        abort_unless($opcion->votacion_id === $votacion->id, 404);
        if ($opcion->votos()->exists()) {
            return back()->with('error', 'No se puede eliminar una opción que ya tiene votos.');
        }
        $opcion->borrarImagen();
        $opcion->delete();
        Bitacora::registrar('Eliminar opción', $opcion->nombre);

        return back()->with('ok', 'Opción eliminada.');
    }

    private function validarOpcion(Request $request, string $reglaImagen): array
    {
        $formatos = implode(',', config('festival.imagen_formatos'));
        $maxKb = config('festival.imagen_max_kb');

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:160'],
            'descripcion' => ['nullable', 'string', 'max:500'],
            // Solo mapas de bits (sin SVG, que puede llevar scripts).
            'imagen' => [$reglaImagen, 'file', 'image', "mimes:{$formatos}", "max:{$maxKb}", 'dimensions:min_width=80,min_height=80,max_width=6000,max_height=6000'],
        ], [
            'imagen.required' => 'Esta categoría requiere la foto del participante.',
            'imagen.image' => 'El archivo debe ser una imagen.',
            'imagen.mimes' => 'La foto debe ser JPG, PNG o WEBP.',
            'imagen.max' => 'La foto no puede pesar más de '.round($maxKb / 1024, 1).' MB.',
            'imagen.dimensions' => 'La foto debe medir entre 80 y 6000 píxeles por lado.',
        ]);

        // El archivo se procesa aparte (guardarImagen); aquí solo van los campos de texto.
        unset($datos['imagen']);

        return $datos;
    }

    /** Guarda la foto con un nombre aleatorio y devuelve la ruta relativa dentro de public/uploads. */
    private function guardarImagen(Request $request): string
    {
        $archivo = $request->file('imagen');

        return $archivo->storeAs('opciones', Str::random(32).'.'.$archivo->extension(), Opcion::DISCO);
    }

    private function validar(Request $request): array
    {
        $datos = $request->validate([
            'categoria_id' => ['required', 'exists:categorias,id'],
            'titulo' => ['required', 'string', 'max:160'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'apertura' => ['nullable', 'date'],
            'cierre' => ['nullable', 'date', 'after:apertura'],
        ]);
        $datos['resultados_publicos'] = $request->boolean('resultados_publicos');

        return $datos;
    }
}
