<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Domain\Materiales;

use App\Modules\Gastos\Contracts\RenglonImputado;
use App\Modules\Rendicion\Domain\Calculo\Decimal;

/**
 * Lo retirado o pedido de un material (N16, N17, N19; ADR-007): la suma de las cantidades y los totales de
 * sus renglones no anulados, por fecha efectiva. Las devoluciones asociadas restan.
 */
final readonly class RetiradoDeMaterial
{
    /**
     * @param  array<string, numeric-string>  $porFecha  "2026-10-02" => "100.000", ordenado por fecha
     * @param  numeric-string  $cantidad
     * @param  numeric-string  $total
     */
    public function __construct(
        public array $porFecha,
        public string $cantidad,
        public string $total,
    ) {}

    public static function nada(): self
    {
        return new self([], Decimal::suma([], Decimal::CANTIDAD), Decimal::suma([]));
    }

    /**
     * @param  list<RenglonImputado>  $renglones  renglones con material
     * @return array<int, self> materialId => retirado
     */
    public static function porMaterial(array $renglones): array
    {
        /** @var array<int, list<RenglonImputado>> $agrupados */
        $agrupados = [];

        foreach ($renglones as $renglon) {
            if ($renglon->materialId !== null) {
                $agrupados[$renglon->materialId][] = $renglon;
            }
        }

        return array_map(self::de(...), $agrupados);
    }

    /**
     * @param  list<RenglonImputado>  $renglones
     */
    private static function de(array $renglones): self
    {
        $porFecha = [];

        foreach ($renglones as $renglon) {
            $porFecha[$renglon->fecha] = Decimal::suma([$porFecha[$renglon->fecha] ?? '0', $renglon->cantidad], Decimal::CANTIDAD);
        }

        ksort($porFecha);

        return new self(
            $porFecha,
            Decimal::suma(array_values($porFecha), Decimal::CANTIDAD),
            Decimal::suma(array_map(static fn (RenglonImputado $r): string => $r->total, $renglones)),
        );
    }
}
