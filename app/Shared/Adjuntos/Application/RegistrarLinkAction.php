<?php

declare(strict_types=1);

namespace App\Shared\Adjuntos\Application;

use App\Modules\Identidad\Domain\Models\User;
use App\Shared\Adjuntos\Adjunto;
use App\Shared\Adjuntos\NombreDeLink;
use App\Shared\Adjuntos\TipoAdjunto;

/**
 * Crea un adjunto temporal de tipo link. Con "Nombre para mostrar", va ese; sin él, la última parte del
 * link, legible, o el dominio si no tiene camino (`NombreDeLink`, PU-207: la misma regla que el front).
 */
final class RegistrarLinkAction
{
    public function handle(User $user, string $contexto, string $url, ?string $nombre): Adjunto
    {
        $etiqueta = trim((string) $nombre);

        return Adjunto::query()->create([
            'contexto' => $contexto,
            'tipo' => TipoAdjunto::Link,
            'url' => $url,
            'nombre' => $etiqueta !== '' ? $etiqueta : NombreDeLink::de($url),
            'subido_por' => $user->getKey(),
        ]);
    }
}
