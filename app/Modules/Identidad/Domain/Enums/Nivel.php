<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Domain\Enums;

/**
 * Nivel de una celda de la matriz sector × función (ADR-006). Editar incluye ver.
 */
enum Nivel: string
{
    case SinAcceso = 'sin_acceso';
    case Ver = 'ver';
    case Editar = 'editar';

    public function alcanza(self $minimo): bool
    {
        return $this->rango() >= $minimo->rango();
    }

    private function rango(): int
    {
        return match ($this) {
            self::SinAcceso => 0,
            self::Ver => 1,
            self::Editar => 2,
        };
    }
}
