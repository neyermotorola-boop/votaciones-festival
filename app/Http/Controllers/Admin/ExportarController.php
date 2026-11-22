<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bitacora;
use App\Models\Categoria;
use App\Models\Edicion;
use App\Models\User;
use App\Models\Votacion;
use App\Services\ExportadorExcel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Exportación a Excel de cada tabla del panel de administración. */
class ExportarController extends Controller
{
    public function __construct(private ExportadorExcel $excel) {}

    public function ediciones()
    {
        $filas = Edicion::withCount('categorias')->orderByDesc('anio')->get()
            ->map(fn ($e) => [$e->anio, $e->nombre, $e->descripcion, $e->categorias_count, $e->activa ? 'Activa' : 'Inactiva', $e->created_at?->format('d/m/Y H:i')]);

        return $this->enviar('ediciones', 'Ediciones',
            ['Año', 'Nombre', 'Descripción', 'Categorías', 'Estado', 'Creada'], $filas, [1 => 8, 2 => 40, 3 => 50, 4 => 12, 5 => 12, 6 => 18]);
    }

    public function categorias()
    {
        $filas = Categoria::with('edicion')->withCount('votaciones')->orderByDesc('edicion_id')->orderBy('nombre')->get()
            ->map(fn ($c) => [$c->edicion->anio, $c->nombre, $c->descripcion, $c->votaciones_count,
                $c->opciones_con_imagen ? 'Con foto' : 'Solo texto', $c->activa ? 'Activa' : 'Inactiva']);

        return $this->enviar('categorias', 'Categorías',
            ['Edición', 'Nombre', 'Descripción', 'Votaciones', 'Participantes', 'Estado'], $filas, [1 => 10, 2 => 30, 3 => 50, 4 => 12, 5 => 14, 6 => 12]);
    }

    public function votaciones()
    {
        $filas = Votacion::with('categoria.edicion')->withCount(['opciones', 'votos'])->latest()->get()
            ->map(fn ($v) => [$v->categoria->edicion->anio, $v->categoria->nombre, $v->titulo, ucfirst($v->estadoEfectivo()),
                $v->apertura?->format('d/m/Y H:i') ?? '-', $v->cierre?->format('d/m/Y H:i') ?? '-',
                $v->votos_por_usuario, $v->resultados_publicos ? 'Sí' : 'No', $v->opciones_count, $v->votos_count]);

        return $this->enviar('votaciones', 'Votaciones',
            ['Edición', 'Categoría', 'Título', 'Estado', 'Apertura', 'Cierre', 'Votos por usuario', 'Resultados públicos', 'Opciones', 'Votos'],
            $filas, [1 => 10, 2 => 24, 3 => 36, 4 => 12, 5 => 17, 6 => 17, 7 => 12, 8 => 12, 9 => 10, 10 => 10]);
    }

    /** Opciones / participantes de una votación con sus votos. */
    public function opciones(Votacion $votacion)
    {
        $total = max(1, $votacion->votos()->count());
        $filas = $votacion->opciones()->withCount('votos')->orderByDesc('votos_count')->orderBy('nombre')->get()
            ->values()
            ->map(fn ($o, $i) => [$i + 1, $o->nombre, $o->descripcion, $o->imagen ? 'Sí' : 'No', $o->votos_count, round($o->votos_count / $total * 100, 1).'%']);

        return $this->enviar('opciones-'.Str::slug($votacion->titulo), 'Opciones',
            ['Posición', 'Opción / participante', 'Descripción', 'Foto', 'Votos', '% del total'], $filas, [1 => 10, 2 => 36, 3 => 46, 4 => 8, 5 => 10, 6 => 12]);
    }

    /** Votos emitidos; respeta los mismos filtros del listado. */
    public function votos(Request $request)
    {
        $consulta = VotoController::consulta(VotoController::filtros($request));

        $filas = (function () use ($consulta) {
            foreach ($consulta->lazyById(500) as $v) {
                yield [$v->id, $v->created_at?->format('d/m/Y H:i:s'), $v->user?->name, $v->user?->email, $v->documento,
                    $v->edicion?->anio, $v->categoria?->nombre, $v->votacion?->titulo, $v->opcion?->nombre];
            }
        })();

        return $this->enviar('votos', 'Votos',
            ['N.º', 'Fecha y hora', 'Votante', 'Correo', 'Documento', 'Edición', 'Categoría', 'Votación', 'Opción elegida'], $filas,
            [1 => 8, 2 => 20, 3 => 28, 4 => 32, 5 => 16, 6 => 10, 7 => 22, 8 => 34, 9 => 32]);
    }

    public function votantes()
    {
        $filas = (function () {
            foreach (User::where('rol', 'votante')->withCount('votos')->orderBy('id')->lazyById(500) as $u) {
                yield [$u->id, $u->name, $u->email, $u->votos_count, $u->created_at?->format('d/m/Y H:i')];
            }
        })();

        return $this->enviar('votantes', 'Votantes', ['ID', 'Nombre', 'Correo', 'Votos emitidos', 'Registrado'], $filas, [1 => 8, 2 => 30, 3 => 34, 4 => 14, 5 => 18]);
    }

    public function bitacora()
    {
        $filas = (function () {
            foreach (Bitacora::with('usuario:id,name')->orderBy('id')->lazyById(500) as $b) {
                yield [$b->created_at?->format('d/m/Y H:i:s'), $b->usuario?->name, $b->accion, $b->detalle];
            }
        })();

        return $this->enviar('bitacora', 'Bitácora', ['Fecha y hora', 'Usuario', 'Acción', 'Detalle'], $filas, [1 => 20, 2 => 28, 3 => 28, 4 => 50]);
    }

    private function enviar(string $archivo, string $hoja, array $cabeceras, iterable $filas, array $anchos)
    {
        $respuesta = $this->excel->descargar($archivo, $hoja, $cabeceras, $filas, $anchos);
        Bitacora::registrar('Exportar Excel', $hoja);

        return $respuesta;
    }
}
