<?php

declare(strict_types=1);

namespace App\Modules\Proveedores\Application\Ports;

/**
 * "Gastado por obra" de un proveedor (F-57): filas por obra (y "(sin obra)") y el total, que se
 * calcula siempre sobre las filas que quedan.
 */
final readonly class GastadoPorObra
{
    private const ESCALA = 2;

    /**
     * @param  list<FilaGastado>  $filas
     */
    public function __construct(public array $filas) {}

    public static function vacio(): self
    {
        return new self([]);
    }

    /** RN-PRV-16: con alcance "solo sus obras" no va la fila "(sin obra)". */
    public function sinFilaSinObra(): self
    {
        return new self(array_values(array_filter($this->filas, static fn (FilaGastado $fila): bool => $fila->obra !== null)));
    }

    public function totalRenglones(): int
    {
        return array_sum(array_map(static fn (FilaGastado $fila): int => $fila->renglones, $this->filas));
    }

    /**
     * @return numeric-string
     */
    public function totalImporte(): string
    {
        return array_reduce(
            $this->filas,
            static fn (string $total, FilaGastado $fila): string => bcadd($total, $fila->importe, self::ESCALA),
            bcadd('0', '0', self::ESCALA),
        );
    }
}
