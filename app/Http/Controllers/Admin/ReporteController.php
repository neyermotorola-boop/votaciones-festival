<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bitacora;
use App\Models\Categoria;
use App\Models\Edicion;
use App\Services\ReporteExcel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReporteController extends Controller
{
    public function index()
    {
        $ediciones = Edicion::with(['categorias' => fn ($q) => $q->withCount('votaciones')->orderBy('nombre')])
            ->orderByDesc('anio')->get();

        return view('admin.reportes.index', [
            'ediciones' => $ediciones,
            'activa' => Edicion::activa()?->id ?? $ediciones->first()?->id,
        ]);
    }

    public function exportar(Request $request, ReporteExcel $reporte)
    {
        $datos = $request->validate([
            'edicion_id' => ['required', 'exists:ediciones,id'],
            'categoria_id' => ['nullable', 'integer'],
            'incluir_votos' => ['nullable', 'boolean'],
        ]);

        $edicion = Edicion::findOrFail($datos['edicion_id']);
        $categoria = null;
        if (! empty($datos['categoria_id'])) {
            $categoria = Categoria::where('edicion_id', $edicion->id)->findOrFail($datos['categoria_id']);
        }

        $nombre = 'votaciones-'.$edicion->anio.'-'.Str::slug($categoria?->nombre ?? 'todas-las-categorias').'-'.now()->format('Ymd-His').'.xlsx';
        $ruta = tempnam(sys_get_temp_dir(), 'rep');

        $reporte->generar($ruta, $edicion, $categoria, $request->boolean('incluir_votos'), $request->user()->name);
        Bitacora::registrar('Exportar Excel', $nombre);

        return response()->download($ruta, $nombre, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
