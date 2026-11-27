<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Application\Carga;

/**
 * Un renglón como llega, ya con la forma validada en el borde (gastos.md §12.2). Cantidad y precio son
 * strings decimales; el `total` que mande el front no se lee (§10 caso 47). `id` solo al corregir: el
 * renglón que se conserva o cambia (los que no vienen, se quitan).
 */
final readonly class DatosRenglon
{
    /**
     * @param  int  $posicion  índice en la lista que mandó el front (para "Renglón 3: …")
     * @param  numeric-string  $cantidad
     * @param  numeric-string  $precioUnitario
     */
    public function __construct(
        public int $posicion,
        public ?int $id,
        public ?string $fecha,
        public string $concepto,
        public string $cantidad,
        public string $precioUnitario,
        public ?int $obraId,
        public ?string $rubroClave,
        public ?int $materialId,
        public ?MaterialNuevo $materialNuevo,
        public ?string $detalle,
        public ?string $empleado = null,
    ) {}
}
