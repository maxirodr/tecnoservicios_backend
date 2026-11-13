<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Domain\Enums;

/**
 * Prioridad de un pedido calculada con los plazos de W-50 (RN-IDE-41).
 */
enum Prioridad: string
{
    case Urgente = 'urgente';
    case Prioritario = 'prioritario';
    case Normal = 'normal';
}
