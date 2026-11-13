<?php

declare(strict_types=1);

namespace App\Shared\Adjuntos\Application;

use App\Modules\Identidad\Domain\Models\User;
use App\Shared\Adjuntos\Adjunto;
use App\Shared\Adjuntos\TipoAdjunto;

/**
 * Crea un adjunto temporal de tipo link. Sin etiqueta, se usa el dominio ("drive.google.com", obras.md §4.8).
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
            'nombre' => $etiqueta !== '' ? $etiqueta : (string) parse_url($url, PHP_URL_HOST),
            'subido_por' => $user->getKey(),
        ]);
    }
}
