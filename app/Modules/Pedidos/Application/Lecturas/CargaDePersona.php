<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Application\Lecturas;

use App\Modules\Identidad\Domain\Models\User;

/** Una persona de mi gente (o yo) con sus números de pedidos abiertos (RN-PED-41, RN-PED-49; DU-P14). */
final readonly class CargaDePersona
{
    public function __construct(
        public User $persona,
        public bool $soyYo,
        public int $abiertas,
        public int $vencidas,
        public int $vencenEstaSemana,
    ) {}
}
