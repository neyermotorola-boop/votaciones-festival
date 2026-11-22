<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use App\Models\Categoria;
use App\Models\Edicion;
use App\Models\Opcion;
use App\Models\Votacion;
use App\Models\Voto;
use Illuminate\Database\UniqueConstraintViolationException;
use App\Notifications\VotoRegistrado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicoController extends Controller
{
    private const YA_VOTO = 'Ya votaste en esta categoría. Solo se permite un voto por categoría y edición.';

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
        $votacion->load('categoria.edicion', 'opciones');

        // Regla central: un solo voto por usuario, categoría y edición.
        $votoCategoria = auth()->check()
            ? Voto::with('opcion', 'votacion')->where('user_id', auth()->id())->where('categoria_id', $votacion->categoria_id)->first()
            : null;
        $votadas = $votoCategoria && $votoCategoria->votacion_id === $votacion->id ? [$votoCategoria->opcion_id] : [];

        $resultados = $votacion->resultados_publicos ? $this->resultados($votacion) : null;

        return view('votacion', compact('votacion', 'votoCategoria', 'votadas', 'resultados'));
    }

    /** Historial del votante: categorías en las que ya participó y las que le faltan. */
    public function misVotos(Request $request)
    {
        $user = $request->user();
        $votos = Voto::with(['edicion', 'categoria', 'votacion', 'opcion'])
            ->where('user_id', $user->id)->latest()->get();

        $edicion = Edicion::activa();
        $pendientes = $edicion
            ? $edicion->categorias()->where('activa', true)
                ->whereHas('votaciones', fn ($q) => $q->where('estado', Votacion::ABIERTA))
                ->whereNotIn('id', $votos->pluck('categoria_id'))->get()
            : collect();

        return view('mis-votos', compact('votos', 'pendientes', 'edicion'));
    }

    public function confirmar(Request $request, Votacion $votacion)
    {
        if ($redirigir = $this->exigirDocumento($request)) {
            return $redirigir;
        }
        $datos = $request->validate(['opcion_id' => ['required', 'integer']]);
        $opcion = $votacion->opciones()->findOrFail($datos['opcion_id']);

        if (self::yaVoto($request->user(), $votacion->categoria_id)) {
            return redirect()->route('votacion', $votacion)->with('error', self::YA_VOTO);
        }

        return view('confirmar', compact('votacion', 'opcion'));
    }

    public function votar(Request $request, Votacion $votacion)
    {
        if ($redirigir = $this->exigirDocumento($request)) {
            return $redirigir;
        }
        $datos = $request->validate(['opcion_id' => ['required', 'integer']]);
        $user = $request->user();

        try {
            $resultado = DB::transaction(function () use ($votacion, $datos, $user) {
                // Bloqueo de la categoría: serializa las solicitudes simultáneas del mismo usuario.
                $categoria = Categoria::whereKey($votacion->categoria_id)->lockForUpdate()->firstOrFail();
                $votacion = Votacion::whereKey($votacion->id)->firstOrFail();

                if (! $categoria->activa) {
                    return 'Esta categoría no está habilitada para votar.';
                }
                if (! $votacion->estaAbierta()) {
                    return 'Esta votación no está abierta.';
                }
                $opcion = Opcion::where('votacion_id', $votacion->id)->find($datos['opcion_id']);
                if (! $opcion) {
                    return 'La opción no pertenece a esta votación.';
                }
                if (self::yaVoto($user, $categoria->id)) {
                    return self::YA_VOTO;
                }

                return Voto::create([
                    'user_id' => $user->id,
                    'edicion_id' => $categoria->edicion_id,
                    'categoria_id' => $categoria->id,
                    'votacion_id' => $votacion->id,
                    'opcion_id' => $opcion->id,
                ]);
            });
        } catch (UniqueConstraintViolationException) {
            // Última defensa: la restricción única de MySQL rechazó un voto duplicado.
            $resultado = self::YA_VOTO;
        }

        // La transacción devuelve un texto (motivo de rechazo) o el voto creado.
        if (is_string($resultado)) {
            return redirect()->route('votacion', $votacion)->with('error', $resultado);
        }

        // Comprobante por correo (se envía en cola; si falla, el voto ya quedó guardado).
        $user->notificarCorreo(new VotoRegistrado($resultado));

        return redirect()->route('votacion.gracias', $votacion);
    }

    /** Para votar, la cuenta debe tener documento registrado (identifica a la persona). */
    private function exigirDocumento(Request $request)
    {
        if (! $request->user()->tieneDocumento()) {
            return redirect()->route('documento')->with('error', 'Registra tu documento de identidad para poder votar.');
        }

        return null;
    }

    /** ¿Esta persona ya votó en la categoría? Se comprueba por cuenta y también por documento. */
    public static function yaVoto(\App\Models\User $user, int $categoriaId): bool
    {
        return Voto::where('categoria_id', $categoriaId)
            ->where(fn ($q) => $q->where('user_id', $user->id)
                ->orWhere(fn ($w) => $w->whereNotNull('documento')->where('documento', $user->documento)))
            ->exists();
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
