<?php

declare(strict_types=1);

namespace App\Modules\Proveedores\Application;

use App\Modules\Proveedores\Domain\Models\Proveedor;

/** Un proveedor ya cargado que se parece al que se está escribiendo (RN-PRV-08). */
final readonly class Parecido
{
    public const MOTIVO_NOMBRE = 'nombre';

    public const MOTIVO_CUIT = 'cuit';

    public function __construct(
        public Proveedor $proveedor,
        public string $motivo,
        public float $puntaje,
    ) {}
}
