<?php

declare(strict_types=1);

namespace App\Modules\Obras\Application\Acceso;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Obras\Domain\Models\Obra;
use App\Modules\Obras\Domain\Models\ObraDocumento;
use App\Modules\Obras\Domain\Models\Presupuesto;
use App\Shared\Http\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Trae el recurso solo si la persona puede verlo (IDOR): una obra fuera de su alcance da 404 "Obra no
 * encontrada", igual que una que no existe (arquitectura §5, HU-014 criterio 6). Si la ve pero no puede editarla: 403.
 */
final readonly class BuscarObra
{
    public function __construct(private AccesoObra $acceso) {}

    /**
     * @param  list<string>  $with
     *
     * @throws HttpResponseException
     */
    public function visible(User $user, int $id, array $with = []): Obra
    {
        $obra = Obra::query()->with($with)->find($id);

        if (! $obra instanceof Obra || ! $this->acceso->verObra($user, $obra)) {
            throw self::noEncontrado('Obra no encontrada.');
        }

        return $obra;
    }

    /**
     * @param  list<string>  $with
     *
     * @throws HttpResponseException|AuthorizationException
     */
    public function editable(User $user, int $id, array $with = []): Obra
    {
        $obra = $this->visible($user, $id, $with);
        $this->exigir($this->acceso->editarObra($user, $obra));

        return $obra;
    }

    /**
     * @throws HttpResponseException
     */
    public function presupuestoVisible(User $user, int $id): Presupuesto
    {
        $presupuesto = Presupuesto::query()->with(['obra', 'adjuntos'])->find($id);

        if (! $presupuesto instanceof Presupuesto || ! $this->acceso->verObra($user, $presupuesto->obra)) {
            throw self::noEncontrado('Presupuesto no encontrado.');
        }

        return $presupuesto;
    }

    /**
     * @throws HttpResponseException|AuthorizationException
     */
    public function presupuestoEditable(User $user, int $id): Presupuesto
    {
        $presupuesto = $this->presupuestoVisible($user, $id);
        $this->exigir($this->acceso->editarObra($user, $presupuesto->obra));

        return $presupuesto;
    }

    /**
     * @throws HttpResponseException|AuthorizationException
     */
    public function documentoEditable(User $user, int $id): ObraDocumento
    {
        $documento = ObraDocumento::query()->with(['obra', 'adjuntos'])->find($id);

        if (! $documento instanceof ObraDocumento || ! $this->acceso->verObra($user, $documento->obra)) {
            throw self::noEncontrado('Documento no encontrado.');
        }

        $this->exigir($this->acceso->editarObra($user, $documento->obra));

        return $documento;
    }

    /**
     * @throws AuthorizationException
     */
    public function exigir(bool $permitido): void
    {
        if (! $permitido) {
            throw new AuthorizationException;
        }
    }

    private static function noEncontrado(string $mensaje): HttpResponseException
    {
        return new HttpResponseException(ApiResponse::error($mensaje, 404));
    }
}
