<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Contracts;

/**
 * Un material de la hoja Materiales de la carga inicial (`importacion.md` §5.5): cantidad escala 3 y precio escala 2,
 * como string decimal (ADR-008). `presupuestoId`: el de la obra (si tiene uno solo, se completa solo).
 */
final readonly class MaterialInicial
{
    /**
     * @param  numeric-string  $cantidadPresupuestada
     * @param  numeric-string|null  $precioPresupuestado
     */
    public function __construct(
        public string $nombre,
        public string $unidad,
        public string $cantidadPresupuestada,
        public ?string $precioPresupuestado,
        public ?int $presupuestoId,
    ) {}
}
