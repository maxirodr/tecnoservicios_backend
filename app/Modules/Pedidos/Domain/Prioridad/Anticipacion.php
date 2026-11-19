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

        // Por la fecha de calendario de cada una, sin importar el huso con que vengan.
        $desde = new DateTimeImmutable($fechaPedido->format('Y-m-d'));
        $hasta = new DateTimeImmutable($fechaEntrega->format('Y-m-d'));

        return (int) $desde->diff($hasta)->format('%r%a');
    }
}
