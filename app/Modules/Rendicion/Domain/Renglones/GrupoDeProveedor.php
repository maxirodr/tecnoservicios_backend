<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Domain\Renglones;

/**
 * Un grupo de W-37 (RN-REN-34, 35): un proveedor, o "Sin documento" (los del genérico), con su total, la
 * cantidad de comprobantes con número (null = "—") y sus conceptos.
 */
final readonly class GrupoDeProveedor
{
    /**
     * @param  numeric-string  $total
     * @param  list<ConceptoAgrupado>  $conceptos
     */
    public function __construct(
        public ?int $proveedorId,
        public ?string $proveedorNombre,
        public bool $sinDocumento,
        public string $total,
        public ?int $comprobantes,
        public array $conceptos,
    ) {}
}
