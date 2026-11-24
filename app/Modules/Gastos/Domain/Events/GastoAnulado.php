<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Domain\Events;

/** Al anular un gasto (W-34): sus renglones dejan de sumar. */
final readonly class GastoAnulado extends EventoDeGasto {}
