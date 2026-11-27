<?php

declare(strict_types=1);

namespace App\Shared\Actividad;

use Closure;

/**
 * Lo que corre la carga inicial desde la consola (`importacion.md` RN-IMP-10, RN-IMP-39): mientras dura, cada
 * actividad queda a nombre de quien aprobó (no hay usuario autenticado en un comando) y con la marca
 * `carga_inicial`. Fuera de `durante()` no cambia nada.
 */
final class CargaInicialEnCurso
{
    private ?int $actorId = null;

    /**
     * @template T
     *
     * @param  Closure(): T  $trabajo
     * @return T
     */
    public function durante(int $actorId, Closure $trabajo): mixed
    {
        $anterior = $this->actorId;
        $this->actorId = $actorId;

        try {
            return $trabajo();
        } finally {
            $this->actorId = $anterior;
        }
    }

    public function actorId(): ?int
    {
        return $this->actorId;
    }

    public function activa(): bool
    {
        return $this->actorId !== null;
    }
}
