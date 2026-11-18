<?php

declare(strict_types=1);

namespace App\Modules\Obras\Application\Montos;

use App\Modules\Obras\Application\Obras\VersionDeObra;
use App\Modules\Obras\Domain\Enums\ModoCarga;
use App\Modules\Obras\Domain\Events\PresupuestoMontosCambiados;
use App\Modules\Obras\Domain\Models\Presupuesto;
use App\Modules\Obras\Domain\Models\PresupuestoMonto;
use App\Shared\Actividad\RegistroActividad;
use App\Shared\Concurrencia\VersionDeFila;
use App\Shared\Domain\Importe;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Connection;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * F-48 / HU-018 (W-13): carga o corrige lo presupuestado de un presupuesto, o por rubro o solo el
 * total, no mezclado (RN-OBR-31): lo nuevo reemplaza lo cargado. Cada valor que cambia queda en el
 * historial con antes → después (RN-OBR-33) y la rendición se entera (`PresupuestoMontosCambiados`).
 * Con la versión del presupuesto que leyó la pantalla (`updated_at`, PU-201): si otra pestaña o
 * persona guardó desde entonces, 409 y no se pisa nada. Guardar un cambio sube la versión del
 * presupuesto y la de la obra.
 */
final readonly class CargarMontosAction
{
    public const ACCION = 'montos_cambiados';

    public const CLAVE_TOTAL = 'total';

    public function __construct(
        private Connection $db,
        private CatalogoRubros $rubros,
        private RegistroActividad $actividad,
        private Dispatcher $eventos,
    ) {}

    /**
     * @param  array<string, Importe|null>  $montos  clave de rubro presupuestable => importe (por rubro)
     *                                               o ['total' => importe] (solo total)
     *
     * @throws HttpResponseException 409 si el presupuesto cambió desde `$version` (PU-201)
     */
    public function handle(Presupuesto $presupuesto, ModoCarga $modo, array $montos, ?CarbonInterface $version = null): void
    {
        $this->db->transaction(function () use ($presupuesto, $modo, $montos, $version): void {
            // Primero la obra y después el presupuesto, el mismo orden que Editar obra: sin deadlocks.
            VersionDeObra::bloquear($presupuesto->obra_id);
            VersionDeFila::bloquearYVerificar($presupuesto, $version);
            $antes = $this->actuales($presupuesto);
            $idsPorClave = $this->rubros->presupuestables()->pluck('id', 'clave');

            PresupuestoMonto::query()->where('presupuesto_id', $presupuesto->id)->delete();

            foreach ($montos as $clave => $importe) {
                if ($importe === null) {
                    continue;
                }

                PresupuestoMonto::query()->create([
                    'presupuesto_id' => $presupuesto->id,
                    'rubro_id' => $clave === self::CLAVE_TOTAL ? null : $idsPorClave[$clave],
                    'monto' => $importe->valor,
                ]);
            }

            $cambios = self::diferencias($antes, array_map(static fn (?Importe $i): ?string => $i?->valor, $montos));

            if ($presupuesto->modo_carga !== $modo) {
                $cambios['modo_carga'] = ['antes' => $presupuesto->modo_carga->value, 'despues' => $modo->value];
                $presupuesto->modo_carga = $modo;
            }

            if ($cambios !== []) {
                // Los montos son otra tabla: el presupuesto igual cambia de versión (PU-201).
                $presupuesto->touch();
                $this->actividad->registrar($presupuesto, self::ACCION, $cambios);
                $this->eventos->dispatch(new PresupuestoMontosCambiados($presupuesto->obra_id));
                VersionDeObra::subir($presupuesto->obra_id);
            }
        });
    }

    /**
     * @return array<string, string> clave => monto actual
     */
    private function actuales(Presupuesto $presupuesto): array
    {
        $claves = $this->rubros->todos()->pluck('clave', 'id');
        $actuales = [];

        foreach (PresupuestoMonto::query()->where('presupuesto_id', $presupuesto->id)->get() as $monto) {
            $actuales[$monto->rubro_id === null ? self::CLAVE_TOTAL : (string) $claves[$monto->rubro_id]] = $monto->monto;
        }

        return $actuales;
    }

    /**
     * @param  array<string, string>  $antes
     * @param  array<string, string|null>  $despues
     * @return array<string, array{antes: string|null, despues: string|null}>
     */
    private static function diferencias(array $antes, array $despues): array
    {
        $cambios = [];

        foreach (array_unique([...array_keys($antes), ...array_keys($despues)]) as $clave) {
            $previo = $antes[$clave] ?? null;
            $nuevo = $despues[$clave] ?? null;

            if ($previo !== $nuevo) {
                $cambios[(string) $clave] = ['antes' => $previo, 'despues' => $nuevo];
            }
        }

        return $cambios;
    }
}
