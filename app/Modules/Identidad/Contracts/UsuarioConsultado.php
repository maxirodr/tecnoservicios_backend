<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Contracts;

/** Una persona del sistema, como la ve la carga inicial (`UsuariosConsulta`). */
final readonly class UsuarioConsultado
{
    public function __construct(
        public int $id,
        public string $nombre,
        public string $email,
        public bool $activo,
        public int $sectorId,
        public string $sectorNombre,
    ) {}
}
