<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Infrastructure;

use App\Modules\Rendicion\Contracts\MaterialesDeObra;
use LogicException;

/** Hasta que Rendicion implemente la lista real: ninguna obra tiene materiales. */
final readonly class MaterialesDeObraNulo implements MaterialesDeObra
{
    public function opciones(int $obraId, ?string $texto): array
    {
        return [];
    }

    public function perteneceAObra(int $materialId, int $obraId): bool
    {
        return false;
    }

    public function crearFueraDePresupuesto(int $obraId, string $nombre, string $unidad, int $userId): int
    {
        throw new LogicException('MaterialesDeObra todavía no está implementado: bindeá un fake en el test.');
    }
}
