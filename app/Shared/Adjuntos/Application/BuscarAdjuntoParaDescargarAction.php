<?php

declare(strict_types=1);

namespace App\Shared\Adjuntos\Application;

use App\Modules\Identidad\Domain\Models\User;
use App\Shared\Adjuntos\Adjunto;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * `GET /api/adjuntos/{id}`: 404 si no existe, si es un link (se abre por su url), si no puede ver el
 * recurso dueño o si es un temporal de otro usuario. Nunca se confirma que el id exista (RN-IDE-47).
 */
final readonly class BuscarAdjuntoParaDescargarAction
{
    public function __construct(private AccesoAdjuntos $acceso, private FilesystemFactory $filesystem) {}

    /**
     * @throws ModelNotFoundException<Adjunto>
     */
    public function handle(User $user, int $id): Adjunto
    {
        $adjunto = Adjunto::query()->with('sujeto')->find($id);

        if ($adjunto === null
            || ! $adjunto->esArchivo()
            || $adjunto->disco === null
            || $adjunto->ruta === null
            || ! $this->acceso->puedeVer($user, $adjunto)
            || ! $this->filesystem->disk($adjunto->disco)->exists($adjunto->ruta)) {
            throw (new ModelNotFoundException)->setModel(Adjunto::class, [$id]);
        }

        return $adjunto;
    }
}
