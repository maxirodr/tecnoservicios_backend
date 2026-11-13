<?php

declare(strict_types=1);

namespace App\Modules\Obras\Domain\Enums;

/**
 * Confirmada → En ejecución → Terminada (RN-OBR-29). La tabla de transiciones vive acá: se avanza
 * o se vuelve de a un paso; cambiarla no toca los casos de uso.
 */
enum ObraEstado: string
{
    case Confirmada = 'confirmada';
    case EnEjecucion = 'en_ejecucion';
    case Terminada = 'terminada';

    /** @var array<string, list<string>> */
    private const TRANSICIONES = [
        'confirmada' => ['en_ejecucion'],
        'en_ejecucion' => ['terminada', 'confirmada'],
        'terminada' => ['en_ejecucion'],
    ];

    public function puedePasarA(self $destino): bool
    {
        return in_array($destino->value, self::TRANSICIONES[$this->value], true);
    }

    public function etiqueta(): string
    {
        return match ($this) {
            self::Confirmada => 'Confirmada',
            self::EnEjecucion => 'En ejecución',
            self::Terminada => 'Terminada',
        };
    }

    /**
     * Pestañas del listado, en el orden de W-05 (RN-OBR-01).
     *
     * @return list<self>
     */
    public static function enOrdenDePestanas(): array
    {
        return [self::EnEjecucion, self::Confirmada, self::Terminada];
    }
}
