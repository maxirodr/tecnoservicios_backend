<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Application\Rendicion;

use App\Modules\Gastos\Contracts\GastadoDeObras;
use Illuminate\Contracts\Cache\Repository;

/**
 * Cache por obra de la rendición (RN-REN-60, ADR-007): clave `rendicion:obra:{id}`, solo el agregado de lo
 * gastado por rubro (nada que dependa de quién mira). Lo presupuestado se lee en cada pedido (una consulta
 * chica de Obras), así que agregar, renombrar o quitar un presupuesto nunca deja la rendición vieja. Lo
 * invalidan los eventos de Gastos y `PresupuestoMontosCambiados` (`InvalidarRendicion`); el vencimiento es
 * solo una red por si se perdiera un evento.
 */
final readonly class GastadoEnCache
{
    public const PREFIJO = 'rendicion:obra:';

    private const SEGUNDOS_DE_RESPALDO = 600;

    public function __construct(
        private Repository $cache,
        private GastadoDeObras $gastado,
    ) {}

    /**
     * @return array<string, numeric-string> rubroClave => total (sin aplicar EPP → INSUMOS)
     */
    public function porRubro(int $obraId): array
    {
        /** @var array<string, numeric-string> */
        return $this->cache->remember(
            self::clave($obraId),
            self::SEGUNDOS_DE_RESPALDO,
            fn (): array => $this->gastado->totalesPorRubro([$obraId])[$obraId] ?? [],
        );
    }

    /**
     * @param  list<int>  $obraIds
     */
    public function olvidar(array $obraIds): void
    {
        foreach (array_unique($obraIds) as $obraId) {
            $this->cache->forget(self::clave($obraId));
        }
    }

    public static function clave(int $obraId): string
    {
        return self::PREFIJO.$obraId;
    }
}
