<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Application\Acceso;

use App\Modules\Gastos\Application\FechaEmpresa;
use App\Modules\Gastos\Domain\Models\Gasto;
use App\Modules\Identidad\Domain\Models\User;
use App\Shared\Http\ApiResponse;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

/**
 * Trae el gasto solo si la persona lo ve (IDOR): uno inexistente, ajeno o sin renglones en sus obras da
 * 404 "No encontramos este gasto." (RN-GAS-48, §7). También arma los 403 y el 409 de anulado.
 */
final readonly class BuscarGasto
{
    public const NO_ENCONTRADO = 'No encontramos este gasto.';

    public const ANULADO = 'Está anulada: no se corrige. Si hace falta, cargala de nuevo.';

    public const RENGLONES_AJENOS = 'Esta factura tiene renglones de obras que no son tuyas: la corrige administración.';

    public function __construct(private AccesoGasto $acceso) {}

    /**
     * @param  list<string>  $with
     *
     * @throws HttpResponseException
     */
    public function visible(User $user, int $id, array $with = []): Gasto
    {
        return $this->exigirVisible($user, Gasto::query()->with(['renglones', ...$with])->find($id));
    }

    /**
     * Lo que se acaba de cargar, para responder el 201 (quien lo cargó ya pasó la autorización de la carga).
     *
     * @param  list<string>  $with
     */
    public function cargado(int $id, array $with): Gasto
    {
        return Gasto::query()->with(['renglones', ...$with])->findOrFail($id);
    }

    /**
     * Dentro de la transacción: bloquea la fila para escritura (RN-GAS-50), verifica que la vea, que la
     * pueda corregir (RN-GAS-49) y que no esté anulada (RN-GAS-41).
     *
     * @throws HttpResponseException 404 · 403 · 409
     */
    public function paraCorregir(User $user, int $id): Gasto
    {
        $gasto = $this->exigirVisible($user, Gasto::query()->with('renglones')->lockForUpdate()->find($id));
        self::exigir($this->acceso->corrigeGasto($user, $gasto), self::RENGLONES_AJENOS);

        if ($gasto->anulado()) {
            throw new HttpResponseException(new JsonResponse(
                ApiResponse::body(['anulado_at' => FechaEmpresa::instante($gasto->anulado_at)], self::ANULADO, null),
                409,
            ));
        }

        return $gasto;
    }

    /**
     * @throws HttpResponseException 403 con el motivo
     */
    public static function exigir(bool $permitido, string $mensaje = 'No tenés permiso para hacer esto.'): void
    {
        if (! $permitido) {
            throw new HttpResponseException(ApiResponse::error($mensaje, 403));
        }
    }

    /**
     * @throws HttpResponseException
     */
    public static function noEncontrado(): HttpResponseException
    {
        return new HttpResponseException(ApiResponse::error(self::NO_ENCONTRADO, 404));
    }

    /**
     * @throws HttpResponseException
     */
    private function exigirVisible(User $user, ?Gasto $gasto): Gasto
    {
        if (! $gasto instanceof Gasto || ! $this->acceso->veGasto($user, $gasto)) {
            throw self::noEncontrado();
        }

        return $gasto;
    }
}
