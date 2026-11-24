<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Contracts;

final readonly class RubroDelCatalogo
{
    public function __construct(
        public int $id,
        public string $clave,
        public string $nombre,
        public bool $cargaPorPeriodo,
    ) {}
}
