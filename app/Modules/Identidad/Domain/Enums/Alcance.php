<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Domain\Enums;

/**
 * Alcance de una celda de la matriz (ADR-006): todas, o solo las propias
 * (obras donde es responsable; en pedidos, los que pidió, tiene, sigue o son de su gente).
 */
enum Alcance: string
{
    case Todas = 'todas';
    case Propias = 'propias';
}
