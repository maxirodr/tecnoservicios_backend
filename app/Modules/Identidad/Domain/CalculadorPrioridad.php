<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Domain;

use App\Modules\Identidad\Domain\Enums\Prioridad;
use DateTimeImmutable;
use DateTimeInterface;

/**
 * RN-IDE-41: con d = días corridos entre la fecha de pedido y la de entrega (sin feriados):
 * d ≤ urgente → Urgente; d ≤ prioritario → Prioritario; si no → Normal. Entrega anterior al pedido → Urgente.
 */
final class CalculadorPrioridad
{
    public function __construct(
        private readonly int $urgenteHastaDias,
        private readonly int $prioritarioHastaDias,
    ) {}

    public function calcular(DateTimeInterface $fechaPedido, DateTimeInterface $fechaEntrega): Prioridad
    {
        $dias = self::diasCorridos($fechaPedido, $fechaEntrega);

        return match (true) {
            $dias <= $this->urgenteHastaDias => Prioridad::Urgente,
            $dias <= $this->prioritarioHastaDias => Prioridad::Prioritario,
            default => Prioridad::Normal,
        };
    }

    private static function diasCorridos(DateTimeInterface $desde, DateTimeInterface $hasta): int
    {
        $inicio = DateTimeImmutable::createFromInterface($desde)->setTime(0, 0);
        $fin = DateTimeImmutable::createFromInterface($hasta)->setTime(0, 0);

        return (int) $inicio->diff($fin)->format('%r%a');
    }
}
