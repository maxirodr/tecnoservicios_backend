<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Domain\Enums;

/** Estado de la reparación (W-49): se elige libremente entre sus valores (resuelta DU-P18). */
enum EquipoEstado: string
{
    case NoIniciada = 'no_iniciada';
    case ParaRetiro = 'para_retiro';
    case EnCurso = 'en_curso';
    case Bloqueada = 'bloqueada';
    case Completada = 'completada';

    public function etiqueta(): string
    {
        return match ($this) {
            self::NoIniciada => 'No iniciada',
            self::ParaRetiro => 'Para retiro',
            self::EnCurso => 'En curso',
            self::Bloqueada => 'Bloqueada',
            self::Completada => 'Completada',
        };
    }
}
