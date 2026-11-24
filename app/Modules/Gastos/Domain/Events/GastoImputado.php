<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Domain\Events;

/** Al cargar un gasto o una factura, desde cualquier pantalla (W-10, W-35, W-46). */
final readonly class GastoImputado extends EventoDeGasto {}
