<?php

declare(strict_types=1);

namespace App\Modules\Obras\Contracts;

final readonly class MontoDePresupuesto
{
    /**
     * @param  numeric-string|null  $monto
     */
    public function __construct(
        public string $numero,
        public ?string $monto,
    ) {}
}
