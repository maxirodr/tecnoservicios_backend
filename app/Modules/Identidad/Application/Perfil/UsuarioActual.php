<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Application\Perfil;

use App\Modules\Identidad\Domain\Enums\Alcance;
use App\Modules\Identidad\Domain\Enums\Nivel;
use App\Modules\Identidad\Domain\Models\User;

/**
 * Lo que devuelve `GET /api/me` (identidad.md §10): usuario con sector y jefe, si tiene gente a
 * cargo (RN-IDE-13) y su matriz efectiva (las 12 funciones).
 */
final readonly class UsuarioActual
{
    /**
     * @param  array<string, array{nivel: Nivel, alcance: Alcance}>  $permisos
     */
    public function __construct(
        public User $user,
        public bool $tieneGente,
        public array $permisos,
    ) {}
}
