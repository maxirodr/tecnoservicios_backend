<?php

declare(strict_types=1);

namespace App\Modules\Importacion\Domain;

/** Ensayo por defecto (RN-IMP-03); confirmada con `--confirmar --aprobo` (RN-IMP-04). */
enum Modo: string
{
    case Ensayo = 'ensayo';
    case Confirmada = 'confirmada';

    public function etiqueta(): string
    {
        return $this === self::Ensayo ? 'Ensayo' : 'Confirmada';
    }
}
