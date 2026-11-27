<?php

declare(strict_types=1);

namespace App\Modules\Importacion\Domain\Lectura;

use DateTimeInterface;

/**
 * Una fila de datos de una hoja (desde la fila 2): su número en la hoja y los valores por el nombre de la columna,
 * como los da la planilla (texto, número, fecha de Excel o vacío). Las columnas que no vinieron no están.
 */
final readonly class FilaLeida
{
    /**
     * @param  array<string, string|int|float|bool|DateTimeInterface|null>  $valores
     */
    public function __construct(
        public int $numero,
        public array $valores,
    ) {}

    public function valor(string $columna): string|int|float|bool|DateTimeInterface|null
    {
        return $this->valores[$columna] ?? null;
    }

    public function texto(string $columna): ?string
    {
        return Valores::texto($this->valor($columna));
    }
}
