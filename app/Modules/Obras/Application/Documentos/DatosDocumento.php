<?php

declare(strict_types=1);

namespace App\Modules\Obras\Application\Documentos;

use App\Modules\Obras\Domain\Enums\TipoDocumento;

/** Certificado u otro documento de la obra (obras.md §4.5) con sus adjuntos (1 a 10). */
final readonly class DatosDocumento
{
    /**
     * @param  list<int>  $adjuntos
     */
    public function __construct(
        public TipoDocumento $tipo,
        public string $nombre,
        public ?string $fecha,
        public array $adjuntos,
    ) {}
}
