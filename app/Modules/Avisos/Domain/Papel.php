<?php

declare(strict_types=1);

namespace App\Modules\Avisos\Domain;

/**
 * Por qué una persona recibe el aviso. Si tiene dos papeles en el mismo evento recibe uno solo, el más
 * directo: propietario (o persona del sector) > solicitante > superior > seguidor (RN-AVI-04).
 */
enum Papel: string
{
    case Propietario = 'propietario';
    case Sector = 'sector';
    case Solicitante = 'solicitante';
    case Superior = 'superior';
    case Seguidor = 'seguidor';

    public function precedencia(): int
    {
        return match ($this) {
            self::Propietario, self::Sector => 0,
            self::Solicitante => 1,
            self::Superior => 2,
            self::Seguidor => 3,
        };
    }
}
