<?php

declare(strict_types=1);

namespace App\Modules\Proveedores\Application\Ports;

/**
 * Qué obras puede ver quien pide "Gastado por obra" (RN-PRV-16): todas, o solo aquellas donde
 * `$userId` es responsable (alcance "propias" de `gastos`, ADR-006). Quien implementa el puerto
 * resuelve esas obras con el contrato de Obras.
 */
final readonly class AlcanceObras
{
    private function __construct(public ?int $userId) {}

    public static function todas(): self
    {
        return new self(null);
    }

    public static function propiasDe(int $userId): self
    {
        return new self($userId);
    }

    public function esTodas(): bool
    {
        return $this->userId === null;
    }
}
