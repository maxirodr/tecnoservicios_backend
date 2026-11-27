<?php

declare(strict_types=1);

namespace App\Modules\Importacion\Infrastructure\Xlsx;

use App\Modules\Importacion\Application\Lectura\TextosDeFaltantes;
use App\Modules\Importacion\Application\Ports\EscritorDeInforme;
use App\Modules\Importacion\Domain\Informe\Aviso;
use App\Modules\Importacion\Domain\Informe\Faltante;
use App\Modules\Importacion\Domain\Informe\Informe;
use App\Modules\Importacion\Domain\Lote;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

/**
 * El informe de cada corrida (`importacion.md` §5.8) con OpenSpout: Resumen, Faltantes, Para completar por sector,
 * Avisos, Rubros por revisar, Personas por revisar y Control por rubro, con las columnas de la spec.
 */
final class InformeXlsx implements EscritorDeInforme
{
    private const RESUMEN_OBRAS = [
        'N.º de presupuesto', 'Trabajo', 'Estado', '¿Entra?', 'Presupuestado total', 'Gastado a cargar', 'Total según su planilla',
        'Diferencia', 'Materiales', 'Con retirado', 'Fecha de corte',
    ];

    private const RESUMEN_TAREAS = [
        'filas' => 'Filas', 'tareas' => 'Tareas que entran', 'completadas' => 'Completadas (no se importan)',
        'documentos' => 'Documentos que entran', 'ya_importadas' => 'Ya importadas', 'con_faltantes' => 'Con faltantes',
    ];

    public function escribir(Informe $informe, string $ruta): void
    {
        $writer = new Writer;
        $writer->openToFile($ruta);
        $negrita = (new Style)->setFontBold();
        $hojas = $this->hojas($informe);
        $primera = true;

        foreach ($hojas as $nombre => [$encabezados, $filas]) {
            if (! $primera) {
                $writer->addNewSheetAndMakeItCurrent();
            }

            $primera = false;
            $writer->getCurrentSheet()->setName($nombre);

            if ($nombre === 'Resumen') {
                foreach ($this->cabecera($informe) as $par) {
                    $writer->addRow(Row::fromValues($par));
                }
                $writer->addRow(Row::fromValues([]));
            }

            $writer->addRow(Row::fromValues($encabezados, $negrita));

            foreach ($filas as $fila) {
                $writer->addRow(Row::fromValues($fila));
            }
        }

        $writer->close();
    }

    /**
     * @return list<array{string, string}>
     */
    private function cabecera(Informe $i): array
    {
        return [
            ['Huella', $i->huellaCorta()],
            ['Lote', $i->lote === Lote::Obras ? 'Obras' : 'Tareas'],
            ['Modo', $i->modo->etiqueta()],
            ['Fecha y hora', $i->fechaHora],
            ['Aprobó', $i->aprobo ?? '—'],
        ];
    }

    /**
     * @return array<string, array{list<string>, list<list<string|int>>}>
     */
    private function hojas(Informe $i): array
    {
        $resumen = $i->lote === Lote::Obras
            ? [self::RESUMEN_OBRAS, array_map(static fn (array $f): array => array_values($f), $i->resumenObras)]
            : [array_values(self::RESUMEN_TAREAS), [array_map(static fn (string $k): int => $i->resumenTareas[$k] ?? 0, array_keys(self::RESUMEN_TAREAS))]];

        return [
            'Resumen' => $resumen,
            'Faltantes' => [
                ['Hoja', 'Fila', 'Obra o Clave', 'Columna', 'Qué falta', 'Lo completa', 'Bloquea'],
                array_map(static fn (Faltante $f): array => [$f->hoja, $f->fila ?? '', $f->sujeto, $f->columna, $f->que, $f->loCompleta, 'Sí'], $i->faltantes),
            ],
            'Para completar por sector' => [
                ['Sector', 'Clave', 'Obra', 'Tarea', 'Qué falta'],
                array_map(static fn (array $p): array => array_values($p), $i->porSector),
            ],
            'Avisos' => [
                ['Hoja', 'Fila', 'Obra o Clave', 'Qué pasa', 'Bloquea'],
                array_map(static fn (Aviso $a): array => [$a->hoja, $a->fila ?? '', $a->sujeto, $a->que, 'No'], $i->avisos),
            ],
            'Rubros por revisar' => [
                ['Como figura', 'Obras donde aparece', 'Importe total', 'Rubro sugerido'],
                array_values(array_map(static fn (array $r): array => [$r['como'], implode(', ', $r['obras']), self::importe($r['importe']), $r['sugerido']], $i->rubrosPorRevisar)),
            ],
            'Personas por revisar' => [
                ['Como figura', 'Filas', 'Usuario sugerido'],
                array_values(array_map(static fn (array $p): array => [$p['como'], implode(', ', $p['filas']), $p['sugerido']], $i->personasPorRevisar)),
            ],
            'Control por rubro' => [
                ['Obra', 'Rubro del sistema', 'Importe a cargar', 'De qué filas viene'],
                array_map(static fn (array $c): array => array_values($c), $i->control),
            ],
        ];
    }

    private static function importe(string $valor): string
    {
        return TextosDeFaltantes::importe($valor);
    }
}
