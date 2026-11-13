<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Modules\Identidad\Contracts\Permisos;
use App\Modules\Identidad\Domain\Enums\Alcance;
use App\Modules\Identidad\Domain\Enums\Nivel;
use App\Modules\Identidad\Domain\Models\User;

/**
 * Matriz en memoria para tests de otros módulos mientras Identidad implementa `Permisos`.
 * Sin celda otorgada: sin acceso.
 */
final class PermisosFalsos implements Permisos
{
    /** @var array<int, array<string, array{nivel: Nivel, alcance: Alcance}>> */
    private array $celdas = [];

    public function otorgar(User $user, string $funcion, Nivel $nivel, Alcance $alcance = Alcance::Todas): self
    {
        $this->celdas[(int) $user->getKey()][$funcion] = ['nivel' => $nivel, 'alcance' => $alcance];

        return $this;
    }

    public function puede(User $user, string $funcion, Nivel $nivelMinimo): bool
    {
        $nivel = $this->celdas[(int) $user->getKey()][$funcion]['nivel'] ?? Nivel::SinAcceso;

        return $nivel !== Nivel::SinAcceso && $nivel->alcanza($nivelMinimo);
    }

    public function alcance(User $user, string $funcion): Alcance
    {
        return $this->celdas[(int) $user->getKey()][$funcion]['alcance'] ?? Alcance::Todas;
    }
}
