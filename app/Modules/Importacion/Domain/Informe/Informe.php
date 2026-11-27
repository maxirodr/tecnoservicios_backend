<?php

declare(strict_types=1);

namespace App\Modules\Importacion\Domain\Informe;

use App\Modules\Importacion\Domain\Lote;
use App\Modules\Importacion\Domain\Modo;

/**
 * Lo que se le devuelve a administración en cada corrida (`importacion.md` §5.8). Se arma mientras se valida y se
 * carga, y lo escribe `EscritorDeInforme`. Tiene los valores de las celdas: viaja solo en el `.xlsx` (RN-IMP-13).
 *
 * @phpstan-type FilaResumenObra array{numero: string, trabajo: string, estado: string, entra: string, presupuestado: string, gastado: string, planilla: string, diferencia: string, materiales: int, con_retirado: int, corte: string}
 * @phpstan-type PorSector array{sector: string, clave: string, obra: string, tarea: string, que: string}
 * @phpstan-type RubroPorRevisar array{como: string, obras: list<string>, importe: numeric-string, sugerido: string}
 * @phpstan-type PersonaPorRevisar array{como: string, filas: list<int>, sugerido: string}
 * @phpstan-type Control array{obra: string, rubro: string, importe: string, de: string}
 */
final class Informe
{
    public const ADMINISTRACION = 'Administración';

    /** @var list<Faltante> */
    public array $faltantes = [];

    /** @var list<PorSector> */
    public array $porSector = [];

    /** @var list<Aviso> */
    public array $avisos = [];

    /** @var array<string, RubroPorRevisar> */
    public array $rubrosPorRevisar = [];

    /** @var array<string, PersonaPorRevisar> */
    public array $personasPorRevisar = [];

    /** @var list<Control> */
    public array $control = [];

    /** @var list<FilaResumenObra> */
    public array $resumenObras = [];

    /** @var array<string, int> conteos del lote tareas (Filas, Tareas que entran…) */
    public array $resumenTareas = [];

    public function __construct(
        public readonly Lote $lote,
        public readonly Modo $modo,
        public readonly string $huella,
        public readonly string $fechaHora,
        public readonly ?string $aprobo,
    ) {}

    public function faltante(Faltante $faltante, ?string $sector = null, string $tarea = '', string $obra = ''): void
    {
        $this->faltantes[] = $faltante;

        if ($sector !== null) {
            $this->porSector[] = ['sector' => $sector, 'clave' => $faltante->sujeto, 'obra' => $obra, 'tarea' => $tarea, 'que' => $faltante->que];
        }
    }

    public function aviso(Aviso $aviso): void
    {
        $this->avisos[] = $aviso;
    }

    /** @param  numeric-string  $importe */
    public function rubroPorRevisar(string $como, string $obra, string $importe, string $sugerido): void
    {
        $actual = $this->rubrosPorRevisar[$como] ?? ['como' => $como, 'obras' => [], 'importe' => '0.00', 'sugerido' => $sugerido];

        if (! in_array($obra, $actual['obras'], true)) {
            $actual['obras'][] = $obra;
        }

        $actual['importe'] = bcadd($actual['importe'], $importe, 2);
        $this->rubrosPorRevisar[$como] = $actual;
    }

    public function personaPorRevisar(string $como, int $fila, string $sugerido): void
    {
        $actual = $this->personasPorRevisar[$como] ?? ['como' => $como, 'filas' => [], 'sugerido' => $sugerido];
        $actual['filas'][] = $fila;
        $this->personasPorRevisar[$como] = $actual;
    }

    /** La huella corta que se muestra y que administración aprueba (RN-IMP-05). */
    public function huellaCorta(): string
    {
        return substr($this->huella, 0, 12);
    }
}
