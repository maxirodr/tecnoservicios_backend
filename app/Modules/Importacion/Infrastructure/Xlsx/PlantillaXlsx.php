<?php

declare(strict_types=1);

namespace App\Modules\Importacion\Infrastructure\Xlsx;

use App\Modules\Importacion\Application\Ports\EscritorDePlantilla;
use App\Modules\Importacion\Domain\Plantilla\Hoja;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use RuntimeException;
use ZipArchive;

/**
 * La plantilla vacía de un lote (`importacion:plantilla`, §5): una hoja por cada una de la spec con sus encabezados,
 * la hoja "Listas" con los valores de cada lista y la hoja "Cómo completar". OpenSpout no escribe validaciones de
 * datos: las listas desplegables se agregan después al XML de cada hoja (`dataValidations` de Office Open XML).
 */
final class PlantillaXlsx implements EscritorDePlantilla
{
    public const HOJA_LISTAS = 'Listas';

    public const HOJA_COMO = 'Cómo completar';

    private const FILAS_CON_LISTA = 1000;

    /** Lo que en una hoja va después de `dataValidations` (CT_Worksheet): se inserta antes del primero que aparezca. */
    private const DESPUES = ['<hyperlinks', '<printOptions', '<pageMargins', '<pageSetup', '<headerFooter', '<drawing', '<legacyDrawing', '<tableParts', '<extLst', '</worksheet>'];

    public function escribir(array $hojas, array $comoCompletar, string $ruta): void
    {
        $writer = new Writer;
        $writer->openToFile($ruta);
        $negrita = (new Style)->setFontBold();
        $listas = [];
        $validaciones = [];

        foreach ($hojas as $indice => $hoja) {
            if ($indice > 0) {
                $writer->addNewSheetAndMakeItCurrent();
            }

            $writer->getCurrentSheet()->setName($hoja->nombre);
            $writer->addRow(Row::fromValues($hoja->columnas, $negrita));
            $validaciones[$indice + 1] = $this->validaciones($hoja, $listas);
        }

        $writer->addNewSheetAndMakeItCurrent();
        $writer->getCurrentSheet()->setName(self::HOJA_LISTAS);
        $this->escribirListas($writer, $listas, $negrita);
        $writer->addNewSheetAndMakeItCurrent();
        $writer->getCurrentSheet()->setName(self::HOJA_COMO);
        $writer->addRow(Row::fromValues(['Columna', 'Cómo se completa'], $negrita));

        foreach ($comoCompletar as $fila) {
            $writer->addRow(Row::fromValues($fila));
        }

        $writer->close();
        $this->agregarListasDesplegables($ruta, $validaciones);
    }

    /**
     * @param  array<string, list<string>>  $listas  columna de Listas ("Hoja · Columna") => valores
     * @return list<string> los `<dataValidation>` de la hoja
     */
    private function validaciones(Hoja $hoja, array &$listas): array
    {
        $xml = [];

        foreach ($hoja->listas as $columna => $valores) {
            $posicion = array_search($columna, $hoja->columnas, true);

            if ($posicion === false || $valores === []) {
                continue;
            }

            $listas["{$hoja->nombre} · {$columna}"] = $valores;
            $letraLista = self::letra(count($listas) - 1);
            $letra = self::letra((int) $posicion);
            $ultima = count($valores) + 1;
            $xml[] = '<dataValidation type="list" allowBlank="1" showErrorMessage="1" sqref="'.$letra.'2:'.$letra.self::FILAS_CON_LISTA.'">'
                ."<formula1>'".self::HOJA_LISTAS."'!\${$letraLista}\$2:\${$letraLista}\${$ultima}</formula1></dataValidation>";
        }

        return $xml;
    }

    /**
     * @param  array<string, list<string>>  $listas
     */
    private function escribirListas(Writer $writer, array $listas, Style $negrita): void
    {
        $writer->addRow(Row::fromValues(array_keys($listas), $negrita));
        $largo = $listas === [] ? 0 : max(array_map(count(...), $listas));

        for ($i = 0; $i < $largo; $i++) {
            $writer->addRow(Row::fromValues(array_map(static fn (array $valores): string => $valores[$i] ?? '', array_values($listas))));
        }
    }

    /**
     * @param  array<int, list<string>>  $validaciones  N.º de hoja (1…) => `<dataValidation>`
     */
    private function agregarListasDesplegables(string $ruta, array $validaciones): void
    {
        $zip = new ZipArchive;

        if ($zip->open($ruta) !== true) {
            throw new RuntimeException('No se pudo abrir la plantilla recién escrita.');
        }

        foreach ($validaciones as $numero => $xml) {
            $parte = "xl/worksheets/sheet{$numero}.xml";
            $hoja = $zip->getFromName($parte);

            if ($xml === [] || $hoja === false) {
                continue;
            }

            $bloque = '<dataValidations count="'.count($xml).'">'.implode('', $xml).'</dataValidations>';
            $zip->addFromString($parte, self::insertar($hoja, $bloque));
        }

        $zip->close();
    }

    private static function insertar(string $hoja, string $bloque): string
    {
        foreach (self::DESPUES as $marca) {
            $posicion = strpos($hoja, $marca);

            if ($posicion !== false) {
                return substr($hoja, 0, $posicion).$bloque.substr($hoja, $posicion);
            }
        }

        return $hoja.$bloque;
    }

    /** 0 → A, 25 → Z, 26 → AA. */
    private static function letra(int $indice): string
    {
        $letra = '';

        for ($n = $indice + 1; $n > 0; $n = intdiv($n - 1, 26)) {
            $letra = chr(65 + ($n - 1) % 26).$letra;
        }

        return $letra;
    }
}
