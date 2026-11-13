<?php

declare(strict_types=1);

namespace App\Shared\Adjuntos\Application;

use App\Modules\Identidad\Domain\Models\User;
use App\Shared\Adjuntos\Adjunto;
use App\Shared\Adjuntos\AdjuntosService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * `DELETE /api/adjuntos/{id}`. Un temporal lo quita solo quien lo subió (el "quitar" del formulario
 * antes de guardar). Uno asociado exige editar el recurso dueño (RN-IDE-48): sin poder verlo → 404
 * (no se confirma que exista); viéndolo pero sin poder editarlo → 403.
 */
final readonly class QuitarAdjuntoAction
{
    public function __construct(
        private AccesoAdjuntos $acceso,
        private AdjuntosService $adjuntos,
    ) {}

    /**
     * @throws ModelNotFoundException<Adjunto>
     * @throws AuthorizationException
     */
    public function handle(User $user, int $id): void
    {
        $adjunto = Adjunto::query()->with('sujeto')->find($id);

        if ($adjunto === null || ! $this->acceso->puedeVer($user, $adjunto)) {
            throw (new ModelNotFoundException)->setModel(Adjunto::class, [$id]);
        }

        if ($adjunto->esTemporal()) {
            // Borrado condicionado: si en el medio se asoció a un recurso, ya no es un temporal.
            if (Adjunto::query()->whereKey($id)->whereNull('sujeto_id')->delete() !== 1) {
                throw (new ModelNotFoundException)->setModel(Adjunto::class, [$id]);
            }

            $this->adjuntos->borrarArchivos(new Collection([$adjunto]));

            return;
        }

        if (! $this->acceso->puedeEditar($user, $adjunto)) {
            throw new AuthorizationException;
        }

        $sujeto = $adjunto->sujeto;

        if ($sujeto !== null) {
            $this->adjuntos->quitar([$adjunto->id], $sujeto);
        }
    }
}
