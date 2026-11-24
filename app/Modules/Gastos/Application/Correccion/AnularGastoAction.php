<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Application\Correccion;

use App\Modules\Gastos\Application\Acceso\BuscarGasto;
use App\Modules\Gastos\Domain\Events\GastoAnulado;
use App\Modules\Gastos\Domain\Models\Gasto;
use App\Modules\Identidad\Domain\Models\User;
use App\Shared\Actividad\RegistroActividad;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Connection;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Carbon;

/**
 * F-46 / HU-073: anular en lugar de borrar, con motivo (RN-GAS-40). Queda tachado, deja de sumar en todo
 * lo que sale de los renglones, conserva adjuntos e historial y su número queda libre (la columna generada
 * `numero_vigente` pasa a null). No se vuelve a activar: anular uno anulado da 409 (RN-GAS-41).
 */
final readonly class AnularGastoAction
{
    public const ACCION = 'anulado';

    public function __construct(
        private Connection $db,
        private BuscarGasto $buscar,
        private RegistroActividad $actividad,
        private Dispatcher $eventos,
    ) {}

    /**
     * @throws HttpResponseException 403 · 404 · 409
     */
    public function handle(User $actor, int $id, string $motivo): Gasto
    {
        return $this->db->transaction(function () use ($actor, $id, $motivo): Gasto {
            $gasto = $this->buscar->paraCorregir($actor, $id);

            $gasto->forceFill([
                'anulado_at' => Carbon::now(),
                'anulado_por' => $actor->id,
                'motivo_anulacion' => $motivo,
            ])->save();

            $this->actividad->registrar($gasto, self::ACCION, ['motivo' => ['antes' => null, 'despues' => $motivo]]);
            $this->eventos->dispatch(new GastoAnulado($gasto->id, $gasto->obraIds()));

            return $gasto;
        });
    }
}
