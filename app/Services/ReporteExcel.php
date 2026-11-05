<?php

namespace App\Services;

use App\Models\Categoria;
use App\Models\Edicion;
use App\Models\Votacion;
use App\Models\Voto;
use Illuminate\Support\Collection;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;

/**
 * Genera el libro de Excel con el resumen, los resultados y el detalle
 * de votos de una edición, opcionalmente filtrado por categoría.
 */
class ReporteExcel
{
    private Style $titulo;

    private Style $cabecera;

    private Style $etiqueta;

    private Style $porcentaje;

    public function __construct()
    {
        $this->titulo = new Style(fontSize: 16, fontBold: true, fontColor: '341B72');
        $this->cabecera = new Style(
            fontBold: true,
            fontColor: Color::WHITE,
            backgroundColor: '341B72',
            cellAlignment: CellAlignment::LEFT,
        );
        $this->etiqueta = new Style(fontBold: true, fontColor: '341B72');
        $this->porcentaje = new Style(format: '0.0%');
    }

    public function generar(string $ruta, Edicion $edicion, ?Categoria $categoria, bool $incluirVotos, string $generadoPor): void
    {
        $votaciones = Votacion::query()
            ->with(['categoria', 'opciones' => fn ($q) => $q->withCount('votos')->orderByDesc('votos_count')->orderBy('nombre')])
            ->withCount('votos')
            ->whereHas('categoria', function ($q) use ($edicion, $categoria) {
                $q->where('edicion_id', $edicion->id);
                if ($categoria) {
                    $q->whereKey($categoria->id);
                }
            })
            ->get()
            ->sortBy(fn ($v) => $v->categoria->nombre.'|'.$v->titulo)
            ->values();

        $writer = new Writer(new Options());
        $writer->openToFile($ruta);

        $this->hojaResumen($writer, $edicion, $categoria, $votaciones, $generadoPor);
        $this->hojaResultados($writer, $votaciones);
        $this->hojaVotaciones($writer, $votaciones);
        if ($incluirVotos) {
            $this->hojaVotos($writer, $votaciones);
        }

        $writer->close();
    }

    private function hojaResumen(Writer $w, Edicion $edicion, ?Categoria $categoria, Collection $votaciones, string $generadoPor): void
    {
        $hoja = $w->getCurrentSheet();
        $hoja->setName('Resumen');
        $hoja->setColumnWidth(30, 1);
        $hoja->setColumnWidth(50, 2);

        $w->addRow(Row::fromValuesWithStyle(['Reporte de votaciones'], $this->titulo));
        $w->addRow(Row::fromValues(['Festival de la Leyenda Vallenata']));
        $w->addRow(Row::fromValues([]));

        $filas = [
            ['Edición', $edicion->nombre.' ('.$edicion->anio.')'],
            ['Categoría', $categoria?->nombre ?? 'Todas las categorías'],
            ['Generado el', now()->format('d/m/Y H:i')],
            ['Generado por', $generadoPor],
            ['', ''],
            ['Categorías incluidas', $votaciones->pluck('categoria_id')->unique()->count()],
            ['Votaciones', $votaciones->count()],
            ['Opciones / participantes', $votaciones->sum(fn ($v) => $v->opciones->count())],
            ['Votos emitidos', (int) $votaciones->sum('votos_count')],
            ['Votantes únicos', Voto::whereIn('votacion_id', $votaciones->pluck('id'))->distinct()->count('user_id')],
        ];
        foreach ($filas as [$k, $v]) {
            $w->addRow(new Row([
                \OpenSpout\Common\Entity\Cell::fromValue($k, $this->etiqueta),
                \OpenSpout\Common\Entity\Cell::fromValue($v),
            ]));
        }
    }

    private function hojaResultados(Writer $w, Collection $votaciones): void
    {
        $hoja = $w->addNewSheetAndMakeItCurrent();
        $hoja->setName('Resultados');
        $hoja->setSheetView((new SheetView())->withFreezeRow(2));
        $hoja->setColumnWidth(24, 1);
        $hoja->setColumnWidth(36, 2);
        $hoja->setColumnWidth(14, 3);
        $hoja->setColumnWidth(34, 4);
        $hoja->setColumnWidth(11, 5, 6, 7);

        $w->addRow(Row::fromValuesWithStyle(['Categoría', 'Votación', 'Estado', 'Opción / participante', 'Posición', 'Votos', '% del total'], $this->cabecera));

        if ($votaciones->isEmpty()) {
            $w->addRow(Row::fromValues(['Sin votaciones cargadas para este filtro.']));
        }

        foreach ($votaciones as $v) {
            $total = max(1, $v->opciones->sum('votos_count'));
            foreach ($v->opciones as $i => $o) {
                $w->addRow(new Row([
                    \OpenSpout\Common\Entity\Cell::fromValue($v->categoria->nombre),
                    \OpenSpout\Common\Entity\Cell::fromValue($v->titulo),
                    \OpenSpout\Common\Entity\Cell::fromValue(ucfirst($v->estadoEfectivo())),
                    \OpenSpout\Common\Entity\Cell::fromValue($o->nombre),
                    \OpenSpout\Common\Entity\Cell::fromValue($i + 1),
                    \OpenSpout\Common\Entity\Cell::fromValue($o->votos_count),
                    \OpenSpout\Common\Entity\Cell::fromValue($o->votos_count / $total, $this->porcentaje),
                ]));
            }
        }
    }

    private function hojaVotaciones(Writer $w, Collection $votaciones): void
    {
        $hoja = $w->addNewSheetAndMakeItCurrent();
        $hoja->setName('Votaciones');
        $hoja->setSheetView((new SheetView())->withFreezeRow(2));
        $hoja->setColumnWidth(24, 1);
        $hoja->setColumnWidth(36, 2);
        $hoja->setColumnWidth(14, 3);
        $hoja->setColumnWidth(18, 4, 5);
        $hoja->setColumnWidth(16, 6, 7, 8, 9);

        $w->addRow(Row::fromValuesWithStyle(['Categoría', 'Votación', 'Estado', 'Apertura', 'Cierre', 'Votos por usuario', 'Resultados públicos', 'Opciones', 'Votos'], $this->cabecera));

        foreach ($votaciones as $v) {
            $w->addRow(Row::fromValues([
                $v->categoria->nombre,
                $v->titulo,
                ucfirst($v->estadoEfectivo()),
                $v->apertura?->format('d/m/Y H:i') ?? '-',
                $v->cierre?->format('d/m/Y H:i') ?? '-',
                $v->votos_por_usuario,
                $v->resultados_publicos ? 'Sí' : 'No',
                $v->opciones->count(),
                $v->votos_count,
            ]));
        }
    }

    private function hojaVotos(Writer $w, Collection $votaciones): void
    {
        $hoja = $w->addNewSheetAndMakeItCurrent();
        $hoja->setName('Detalle de votos');
        $hoja->setSheetView((new SheetView())->withFreezeRow(2));
        $hoja->setColumnWidth(20, 1);
        $hoja->setColumnWidth(28, 2);
        $hoja->setColumnWidth(34, 3);
        $hoja->setColumnWidth(24, 4);
        $hoja->setColumnWidth(36, 5);
        $hoja->setColumnWidth(34, 6);

        $w->addRow(Row::fromValuesWithStyle(['Fecha y hora', 'Votante', 'Correo', 'Categoría', 'Votación', 'Opción elegida'], $this->cabecera));

        $porVotacion = $votaciones->keyBy('id');
        Voto::query()
            ->with(['user:id,name,email', 'opcion:id,nombre'])
            ->whereIn('votacion_id', $porVotacion->keys())
            ->orderBy('id')
            ->chunkById(500, function ($votos) use ($w, $porVotacion) {
                foreach ($votos as $voto) {
                    $v = $porVotacion[$voto->votacion_id];
                    $w->addRow(Row::fromValues([
                        $voto->created_at?->format('d/m/Y H:i:s'),
                        $voto->user?->name,
                        $voto->user?->email,
                        $v->categoria->nombre,
                        $v->titulo,
                        $voto->opcion?->nombre,
                    ]));
                }
            });
    }
}
