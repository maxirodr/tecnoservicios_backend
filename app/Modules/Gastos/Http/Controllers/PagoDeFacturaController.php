<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Http\Controllers;

use App\Modules\Gastos\Application\Acceso\BuscarGasto;
use App\Modules\Gastos\Application\Lecturas\LecturaDeGasto;
use App\Modules\Gastos\Application\Pagos\CambiarPagoAction;
use App\Modules\Gastos\Application\Rubros;
use App\Modules\Gastos\Http\Requests\MarcarPagoRequest;
use App\Modules\Gastos\Http\Resources\GastoDetalleResource;
use App\Modules\Identidad\Domain\Models\User;
use App\Shared\Http\RespondsWithEnvelope;
use App\Shared\Http\UsuarioAutenticado;
use Illuminate\Http\JsonResponse;

/** DU-C03 · W-34: marcar una factura como paga o impaga (RN-GAS-59). Responde `GastoDetalle`. */
final class PagoDeFacturaController
{
    use RespondsWithEnvelope;
    use UsuarioAutenticado;

    public function __construct(
        private readonly CambiarPagoAction $cambiar,
        private readonly BuscarGasto $buscar,
        private readonly LecturaDeGasto $lectura,
        private readonly Rubros $rubros,
    ) {}

    public function paga(MarcarPagoRequest $request, int $gasto): JsonResponse
    {
        $user = $this->usuario($request);
        $this->cambiar->handle($user, $gasto, $request->fecha(), $request->versionEsperada());

        return $this->ok($this->detalle($user, $gasto), 'Marcamos la factura como paga.');
    }

    public function impaga(MarcarPagoRequest $request, int $gasto): JsonResponse
    {
        $user = $this->usuario($request);
        $this->cambiar->handle($user, $gasto, null, $request->versionEsperada());

        return $this->ok($this->detalle($user, $gasto), 'Marcamos la factura como impaga.');
    }

    /**
     * @return array<string, mixed>
     */
    private function detalle(User $user, int $id): array
    {
        return GastoDetalleResource::detalle($this->lectura->de($user, $this->buscar->visible($user, $id, LecturaDeGasto::DETALLE)), $this->rubros);
    }
}
