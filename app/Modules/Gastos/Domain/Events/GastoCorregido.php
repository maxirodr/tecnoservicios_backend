<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Domain\Events;

/** Al corregir un gasto (W-34). `obraIds` = las obras de antes ∪ las de después. */
final readonly class GastoCorregido extends EventoDeGasto {}
