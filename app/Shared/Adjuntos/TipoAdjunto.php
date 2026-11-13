<?php

declare(strict_types=1);

namespace App\Shared\Adjuntos;

/**
 * Un adjunto es un archivo subido o un link, nunca los dos (ADR-014).
 */
enum TipoAdjunto: string
{
    case Archivo = 'archivo';
    case Link = 'link';
}
