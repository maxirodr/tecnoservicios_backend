<?php

declare(strict_types=1);

namespace App\Modules\Obras\Domain\Enums;

/** Los dos contactos del cliente (obras.md §4.4): uno de cada tipo por obra. */
enum TipoContacto: string
{
    case Solicitante = 'solicitante';
    case Comprador = 'comprador';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Solicitante => 'Contacto solicitante',
            self::Comprador => 'Contacto comprador',
        };
    }
}
