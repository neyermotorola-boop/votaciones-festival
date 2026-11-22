<?php

namespace App\Services;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Exporta una tabla simple (cabeceras + filas) a un archivo .xlsx con los
 * colores institucionales. Las filas pueden venir de un generador para no
 * cargar en memoria tablas grandes (p. ej. votos).
 */
class ExportadorExcel
{
    public const ROJO = 'D3121B';

    /**
     * @param  array<int, string>  $cabeceras
     * @param  iterable<array<int, mixed>>  $filas
     * @param  array<int, int>  $anchos  ancho por columna (1 = primera)
     */
    public function descargar(string $archivo, string $hoja, array $cabeceras, iterable $filas, array $anchos = []): BinaryFileResponse
    {
        $ruta = tempnam(sys_get_temp_dir(), 'exp');

        $writer = new Writer(new Options());
        $writer->openToFile($ruta);

        $sheet = $writer->getCurrentSheet();
        $sheet->setName(mb_substr($hoja, 0, 31));
        $sheet->setSheetView((new SheetView())->withFreezeRow(2));
        foreach ($anchos as $col => $ancho) {
            $sheet->setColumnWidth($ancho, $col);
        }

        $writer->addRow(Row::fromValuesWithStyle($cabeceras, new Style(
            fontBold: true,
            fontColor: Color::WHITE,
            backgroundColor: self::ROJO,
            cellAlignment: CellAlignment::LEFT,
        )));

        $n = 0;
        foreach ($filas as $fila) {
            $writer->addRow(Row::fromValues(array_map(fn ($v) => $v ?? '', $fila)));
            $n++;
        }
        if ($n === 0) {
            $writer->addRow(Row::fromValues(['Sin registros.']));
        }

        $writer->close();

        return response()->download($ruta, $archivo.'-'.now()->format('Ymd-His').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
