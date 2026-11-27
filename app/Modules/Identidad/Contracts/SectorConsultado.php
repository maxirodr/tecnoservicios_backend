<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Contracts;

/** Un sector del organigrama, como lo ve la carga inicial (`UsuariosConsulta`). */
final readonly class SectorConsultado
{
    public function __construct(
        public int $id,
        public string $nombre,
        public bool $activo,
    ) {}
}
