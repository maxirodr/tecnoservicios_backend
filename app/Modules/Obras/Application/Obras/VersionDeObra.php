<?php

declare(strict_types=1);

namespace App\Modules\Obras\Application\Obras;

use App\Modules\Obras\Domain\Models\Obra;

/**
 * La versión de la obra (`updated_at`, PU-002) cubre también sus presupuestos (PU-101): cualquier
 * cambio de un presupuesto, sus adjuntos o sus montos la sube, así quien tiene abierto Editar obra
 * recibe 409 en vez de pisarlo. Dentro de la transacción se bloquea primero la obra y después el
 * presupuesto, el mismo orden que usa Editar obra, para no cruzar bloqueos.
 */
final class VersionDeObra
{
    public static function bloquear(int $obraId): void
    {
        Obra::query()->whereKey($obraId)->lockForUpdate()->value('id');
    }

    /** Sin eventos ni historial: solo cambia la versión. */
    public static function subir(int $obraId): void
    {
        Obra::query()->whereKey($obraId)->toBase()->update(['updated_at' => (new Obra)->freshTimestampString()]);
    }
}
