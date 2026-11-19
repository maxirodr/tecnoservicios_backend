<?php

declare(strict_types=1);

namespace App\Modules\Avisos\Domain;

/** Una persona que recibe un aviso de un evento, con el tipo y el papel que le tocan. */
final readonly class Destinatario
{
    public function __construct(
        public int $userId,
        public TipoAviso $tipo,
        public Papel $papel,
    ) {}
}
