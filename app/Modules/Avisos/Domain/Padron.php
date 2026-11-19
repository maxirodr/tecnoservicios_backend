<?php

declare(strict_types=1);

namespace App\Modules\Avisos\Domain;

/**
 * Lo que hace falta saber de las personas para decidir destinatarios, ya resuelto con Identidad:
 * quiénes están activas, las personas activas del sector del pedido y el superior de quien lo pidió.
 */
final readonly class Padron
{
    /**
     * @param  list<int>  $activos
     * @param  list<int>  $personasDelSector  activas, en el orden en que se avisan
     */
    public function __construct(
        public array $activos,
        public array $personasDelSector = [],
        public ?int $superiorDelSolicitante = null,
    ) {}

    public function activo(int $userId): bool
    {
        return in_array($userId, $this->activos, true);
    }
}
