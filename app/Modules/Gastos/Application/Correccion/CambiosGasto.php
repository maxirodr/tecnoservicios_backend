<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Application\Correccion;

use App\Modules\Gastos\Application\Carga\DatosRenglon;

/**
 * Lo que vino en `PATCH /api/gastos/{id}` (RN-GAS-37): solo los campos de cabecera presentes; `renglones`
 * y `adjuntos` son la lista completa (null = no se tocan). Tipo de documento y origen no se leen.
 */
final readonly class CambiosGasto
{
    /**
     * @param  array<string, string|int|null>  $cabecera  numero, fecha, proveedor_id, rendido_por, periodo_desde, periodo_hasta
     * @param  list<DatosRenglon>|null  $renglones
     * @param  list<int>|null  $adjuntos
     */
    public function __construct(
        public array $cabecera,
        public ?array $renglones,
        public ?array $adjuntos,
        public bool $confirmarRemitoRepetido = false,
        public bool $confirmarSuperposicion = false,
    ) {}

    public function trae(string $campo): bool
    {
        return array_key_exists($campo, $this->cabecera);
    }
}
