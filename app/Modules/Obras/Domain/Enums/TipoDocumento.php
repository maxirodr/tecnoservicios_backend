<?php

declare(strict_types=1);

namespace App\Modules\Obras\Domain\Enums;

/** Documentos de la obra (obras.md §4.5): el certificado exige fecha; "otro", no. */
enum TipoDocumento: string
{
    case Certificado = 'certificado';
    case Otro = 'otro';

    public function exigeFecha(): bool
    {
        return $this === self::Certificado;
    }

    public function etiqueta(): string
    {
        return match ($this) {
            self::Certificado => 'el certificado',
            self::Otro => 'el documento',
        };
    }
}
