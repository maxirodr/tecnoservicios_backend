<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Contracts;

use App\Modules\Identidad\Domain\Enums\Alcance;
use App\Modules\Identidad\Domain\Enums\Nivel;
use App\Modules\Identidad\Domain\Models\User;

/**
 * Puerta única de autorización: lee la matriz sector × función (ADR-006).
 * `$funcion` es una clave del catálogo (pedidos, obras, gastos, rendicion, …).
 */
interface Permisos
{
    public function puede(User $user, string $funcion, Nivel $nivelMinimo): bool;

    public function alcance(User $user, string $funcion): Alcance;
}
