<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Domain\Prioridad;

use DateTimeImmutable;
use DateTimeInterface;

/** "N días entre el pedido y la entrega": días corridos, sin feriados (resuelta DU-04); negativo si la entrega es anterior. */
final class Anticipacion
{
    public static function dias(?DateTimeInterface $fechaPedido, ?DateTimeInterface $fechaEntrega): ?int
    {
        if ($fechaPedido === null || $fechaEntrega === null) {
            return null;
        }

        $desde = DateTimeImmutable::createFromInterface($fechaPedido)->setTime(0, 0);
        $hasta = DateTimeImmutable::createFromInterface($fechaEntrega)->setTime(0, 0);

        return (int) $desde->diff($hasta)->format('%r%a');
    }
}
