<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Application\Pagos;

use App\Modules\Gastos\Application\Acceso\AccesoGasto;
use App\Modules\Gastos\Application\Acceso\BuscarGasto;
use App\Modules\Gastos\Application\FechaEmpresa;
use App\Modules\Gastos\Domain\Legible;
use App\Modules\Gastos\Domain\Models\Gasto;
use App\Modules\Identidad\Domain\Enums\ClaveFuncion;
use App\Modules\Identidad\Domain\Enums\Nivel;
use App\Modules\Identidad\Domain\Models\User;
use App\Shared\Actividad\RegistroActividad;
use App\Shared\Concurrencia\VersionDeFila;
use App\Shared\Http\ApiResponse;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Connection;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

/**
 * DU-C03 (RN-GAS-58…60): marcar una factura como paga, con la fecha del pago, o como impaga. Con `pagos = editar`
 * y viendo la factura (si no la ve, 404). Con la fila bloqueada y la versión verificada (409); una anulada no se
 * marca (409). Si no cambia nada, no registra nada. Cambia la versión y la "Última edición"; no toca la
 * rendición (sin evento).
 */
final readonly class CambiarPagoAction
{
    public const PAGADA = 'pagada';

    public const IMPAGADA = 'impagada';

    public const CAMPO = 'pago';

    public const SOLO_FACTURAS = 'Solo las facturas se marcan como pagas o impagas.';

    public const ANULADA = 'Está anulada: no se marca como paga ni impaga.';

    public function __construct(
        private Connection $db,
        private BuscarGasto $buscar,
        private AccesoGasto $acceso,
        private RegistroActividad $actividad,
    ) {}

    /**
     * @param  string|null  $fecha  "AAAA-MM-DD" (ya validada ≤ hoy) para marcar paga; null para marcar impaga
     *
     * @throws HttpResponseException 403 · 404 · 409 · 422
     */
    public function handle(User $actor, int $id, ?string $fecha, ?CarbonInterface $version): Gasto
    {
        BuscarGasto::exigir($this->acceso->puede($actor, ClaveFuncion::Pagos, Nivel::Editar));

        return $this->db->transaction(function () use ($actor, $id, $fecha, $version): Gasto {
            $gasto = $this->buscar->visible($actor, $id);
            VersionDeFila::bloquearYVerificar($gasto, $version);
            $this->exigirFacturaVigente($gasto);

            $antes = $gasto->pagado_at?->toDateString();

            if ($antes === $fecha) {
                return $gasto;
            }

            $gasto->forceFill([
                'pagado_at' => $fecha,
                'pagado_por' => $fecha === null ? null : $actor->id,
                'updated_by' => $actor->id,
            ])->save();

            $this->actividad->registrar($gasto, $fecha === null ? self::IMPAGADA : self::PAGADA, [
                self::CAMPO => ['antes' => self::legible($antes), 'despues' => self::legible($fecha)],
            ]);

            return $gasto;
        });
    }

    /**
     * @throws HttpResponseException 422 · 409
     */
    private function exigirFacturaVigente(Gasto $gasto): void
    {
        if (! $gasto->tieneEstadoDePago()) {
            throw new HttpResponseException(ApiResponse::error(self::SOLO_FACTURAS, 422, [self::CAMPO => [self::SOLO_FACTURAS]]));
        }

        if ($gasto->anulado()) {
            throw new HttpResponseException(new JsonResponse(
                ApiResponse::body(['anulado_at' => FechaEmpresa::instante($gasto->anulado_at)], self::ANULADA, null),
                409,
            ));
        }
    }

    private static function legible(?string $fecha): ?string
    {
        return $fecha === null ? null : Legible::fecha(CarbonImmutable::parse($fecha));
    }
}
