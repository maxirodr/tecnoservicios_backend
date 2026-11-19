<?php

declare(strict_types=1);

namespace App\Modules\Avisos\Contracts;

use DateTimeImmutable;

/** Avisos generados por un mismo evento de un pedido, para "sistema avisó a …" del recorrido (RN-AVI-28). */
final readonly class AvisoEnviado
{
    /**
     * @param  list<array{id: int, nombre: string}>  $destinatarios
     */
    public function __construct(
        public string $tipo,
        public array $destinatarios,
        public DateTimeImmutable $createdAt,
    ) {}
}
