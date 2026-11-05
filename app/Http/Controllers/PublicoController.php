<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use App\Models\Categoria;
use App\Models\Edicion;
use App\Models\Opcion;
use App\Models\Votacion;
use App\Models\Voto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicoController extends Controller
{
    public function home()
    {
        $edicion = Edicion::activa();
        $categorias = $edicion
            ? $edicion->categorias()->where('activa', true)->withCount(['votaciones as abiertas_count' => fn ($q) => $q->where('estado', Votacion::ABIERTA)])->get()
            : collect();

        return view('home', compact('edicion', 'categorias'));
    }

    public function ediciones()
    {
        $ediciones = Edicion::withCount(['categorias as categorias_count' => fn ($q) => $q->where('activa', true)])
            ->orderByDesc('anio')->get();

        return view('ediciones', compact('ediciones'));
    }

    public function edicion(Edicion $edicion)
    {
        $categorias = $edicion->categorias()->where('activa', true)
            ->withCount(['votaciones as abiertas_count' => fn ($q) => $q->where('estado', Votacion::ABIERTA)])->get();

        return view('edicion', compact('edicion', 'categorias'));
    }

    public function categoria(Categoria $categoria)
    {
        abort_unless($categoria->activa, 404);
        $categoria->load('edicion');
        $votaciones = $categoria->votaciones()->where('estado', '!=', Votacion::BORRADOR)->withCount('opciones')->get();

        return view('categoria', compact('categoria', 'votaciones'));
    }

    public function votacion(Votacion $votacion)
    {
        abort_if($votacion->estado === Votacion::BORRADOR, 404);
        $votacion->load('categoria', 'opciones');

        $misVotos = auth()->check() ? $votacion->votosDe(auth()->user()) : 0;
        $votadas = auth()->check()
            ? $votacion->votos()->where('user_id', auth()->id())->pluck('opcion_id')->all()
            : [];

        $resultados = $votacion->resultados_publicos ? $this->resultados($votacion) : null;

        return view('votacion', compact('votacion', 'misVotos', 'votadas', 'resultados'));
    }

    public function confirmar(Request $request, Votacion $votacion)
    {
        $datos = $request->validate(['opcion_id' => ['required', 'integer']]);
        $opcion = $votacion->opciones()->findOrFail($datos['opcion_id']);

        return view('confirmar', compact('votacion', 'opcion'));
    }

    public function votar(Request $request, Votacion $votacion)
    {
        $datos = $request->validate(['opcion_id' => ['required', 'integer']]);
        $user = $request->user();

        $error = DB::transaction(function () use ($votacion, $datos, $user) {
            // Bloqueo para evitar votos duplicados concurrentes.
            $votacion = Votacion::whereKey($votacion->id)->lockForUpdate()->first();

            if (! $votacion->estaAbierta()) {
                return 'Esta votación no está abierta.';
            }
            $opcion = Opcion::where('votacion_id', $votacion->id)->find($datos['opcion_id']);
            if (! $opcion) {
                return 'La opción no pertenece a esta votación.';
            }
            if (Voto::where('user_id', $user->id)->where('votacion_id', $votacion->id)->count() >= $votacion->votos_por_usuario) {
                return 'Ya alcanzaste el máximo de votos permitidos en esta votación.';
            }
            if (Voto::where('user_id', $user->id)->where('opcion_id', $opcion->id)->exists()) {
                return 'Ya votaste por esta opción.';
            }

            Voto::create(['user_id' => $user->id, 'votacion_id' => $votacion->id, 'opcion_id' => $opcion->id]);

            return null;
        });

        if ($error) {
            return redirect()->route('votacion', $votacion)->with('error', $error);
        }

        return redirect()->route('votacion.gracias', $votacion);
    }

    public function gracias(Votacion $votacion)
    {
        return view('gracias', compact('votacion'));
    }

    /** @return \Illuminate\Support\Collection<int, Opcion> */
    public static function resultados(Votacion $votacion)
    {
        $opciones = $votacion->opciones()->withCount('votos')->get();
        $total = max(1, $opciones->sum('votos_count'));

        return $opciones->map(function ($o) use ($total) {
            $o->porcentaje = round($o->votos_count / $total * 100, 1);

            return $o;
        })->sortByDesc('votos_count')->values();
    }
}
