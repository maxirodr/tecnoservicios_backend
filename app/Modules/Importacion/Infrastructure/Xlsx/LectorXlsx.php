<?php

declare(strict_types=1);

namespace App\Modules\Importacion\Infrastructure\Xlsx;

use App\Modules\Importacion\Application\Ports\LectorDePlantillas;
use App\Modules\Importacion\Domain\Lectura\FilaLeida;
use App\Modules\Importacion\Domain\Lectura\PlantillaInvalida;
use App\Modules\Importacion\Domain\Lectura\Valores;
use App\Modules\Importacion\Domain\Plantilla\Hoja;
use DateInterval;
use DateTimeInterface;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Options;
use OpenSpout\Reader\XLSX\Reader;

/**
 * `LectorDePlantillas` con OpenSpout (`importacion.md` §11.2): hojas y columnas por su nombre, sin contar mayúsculas,
 * tildes ni espacios; las fechas de Excel llegan como fecha y los números como número (la conversión a texto decimal
 * es de `Valores`). Las filas vacías se cuentan para que el N.º de fila del informe sea el de la planilla.
 */
final class LectorXlsx implements LectorDePlantillas
{
    private const FILA_DE_DATOS = 2;

    public function leer(string $ruta, array $hojas): array
    {
        $porClave = [];

        foreach ($hojas as $hoja) {
            $porClave[Valores::clave($hoja->nombre)] = $hoja;
        }

        $options = new Options;
        $options->SHOULD_PRESERVE_EMPTY_ROWS = true;
        $reader = new Reader($options);
        $reader->open($ruta);
        $leidas = [];

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                $hoja = $porClave[Valores::clave($sheet->getName())] ?? null;

                if ($hoja !== null && ! isset($leidas[$hoja->nombre])) {
                    $leidas[$hoja->nombre] = $this->filas($hoja, $sheet->getRowIterator());
                }
            }
        } finally {
            $reader->close();
        }

        foreach ($hojas as $hoja) {
            if ($hoja->obligatoria && ! isset($leidas[$hoja->nombre])) {
                throw PlantillaInvalida::faltaHoja($hoja->nombre);
            }
        }

        return array_map(static fn (?array $filas): array => $filas ?? [], [...array_fill_keys(array_map(static fn (Hoja $h): string => $h->nombre, $hojas), null), ...$leidas]);
    }

    /**
     * @param  iterable<Row>  $rows
     * @return list<FilaLeida>
     *
     * @throws PlantillaInvalida
     */
    private function filas(Hoja $hoja, iterable $rows): array
    {
        $encabezados = null;
        $filas = [];
        $numero = 0;

        foreach ($rows as $row) {
            $numero++;
            $valores = array_map(self::valor(...), $row->toArray());

            if ($encabezados === null) {
                $encabezados = $this->encabezados($hoja, $valores);

                continue;
            }

            $fila = [];

            foreach ($encabezados as $indice => $columna) {
                $fila[$columna] = $valores[$indice] ?? null;
            }

            if ($numero >= self::FILA_DE_DATOS && array_filter($fila, static fn (mixed $v): bool => Valores::texto($v) !== null) !== []) {
                $filas[] = new FilaLeida($numero, $fila);
            }
        }

        if ($hoja->obligatoria || $filas !== []) {
            self::exigirColumnas($hoja, $encabezados ?? []);
        }

        return $filas;
    }

    /**
     * Fila 1 = encabezados, por su nombre en la plantilla (las desconocidas quedan con el suyo y se ignoran).
     *
     * @param  list<string|int|float|bool|DateTimeInterface|null>  $valores
     * @return array<int, string> índice => nombre de la columna en la plantilla
     */
    private function encabezados(Hoja $hoja, array $valores): array
    {
        $conocidas = [];

        foreach ($hoja->columnas as $columna) {
            $conocidas[Valores::clave($columna)] = $columna;
        }

        $encabezados = [];

        foreach ($valores as $indice => $valor) {
            $texto = Valores::texto($valor);

            if ($texto !== null) {
                $encabezados[$indice] = $conocidas[Valores::clave($texto)] ?? $texto;
            }
        }

        return $encabezados;
    }

    /**
     * Una hoja opcional vacía no frena (RN-IMP-11); si trae datos, o es obligatoria, necesita sus columnas.
     *
     * @param  array<int, string>  $encabezados
     *
     * @throws PlantillaInvalida
     */
    private static function exigirColumnas(Hoja $hoja, array $encabezados): void
    {
        foreach ($hoja->obligatorias as $obligatoria) {
            if (! in_array($obligatoria, $encabezados, true)) {
                throw PlantillaInvalida::faltaColumna($obligatoria, $hoja->nombre);
            }
        }
    }

    private static function valor(mixed $valor): string|int|float|bool|DateTimeInterface|null
    {
        return match (true) {
            $valor instanceof DateInterval, $valor === '' => null,
            is_string($valor), is_int($valor), is_float($valor), is_bool($valor), $valor instanceof DateTimeInterface => $valor,
            default => null,
        };
    }
}
