<?php

declare(strict_types=1);

namespace App\Modules\Obras\Contracts;

/** Un documento de la minuta para Documentos de la obra (`importacion.md` RN-IMP-38). Fecha "AAAA-MM-DD". */
final readonly class DocumentoInicial
{
    /**
     * @param  'certificado'|'otro'  $tipo
     * @param  non-empty-list<string>  $links
     */
    public function __construct(
        public string $tipo,
        public string $nombre,
        public ?string $fecha,
        public array $links,
    ) {}
}
