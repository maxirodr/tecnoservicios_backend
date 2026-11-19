<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Domain\Enums;

/** Los cuatro estados del desplegable de la minuta (pedidos.md §3); "abiertos" = los tres primeros. */
enum PedidoEstado: string
{
    case NoIniciada = 'no_iniciada';
    case EnCurso = 'en_curso';
    case Bloqueada = 'bloqueada';
    case Completada = 'completada';

    public function abierto(): bool
    {
        return $this !== self::Completada;
    }

    /**
     * @return list<string>
     */
    public static function abiertos(): array
    {
        return [self::NoIniciada->value, self::EnCurso->value, self::Bloqueada->value];
    }

    public function etiqueta(): string
    {
        return match ($this) {
            self::NoIniciada => 'No iniciada',
            self::EnCurso => 'En curso',
            self::Bloqueada => 'Bloqueada',
            self::Completada => 'Completada',
        };
    }
}
