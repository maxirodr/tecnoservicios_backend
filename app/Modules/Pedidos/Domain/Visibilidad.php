<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Domain;

/**
 * Qué pedidos ve una persona en los listados (RN-PED-32…34): todos (Pedidos ≥ Ver, alcance todas) o lo
 * propio (pidió, tiene, sigue, en su sector sin propietario) más, con Ver y alcance propias, los de su
 * gente (`gente` null = todos, Dirección).
 */
final readonly class Visibilidad
{
    /**
     * @param  list<int>|null  $gente
     */
    public function __construct(
        public bool $todos,
        public int $userId,
        public int $sectorId,
        public bool $conGente,
        public ?array $gente,
    ) {}

    public static function todos(): self
    {
        return new self(true, 0, 0, false, []);
    }
}
