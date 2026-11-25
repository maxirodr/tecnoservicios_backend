<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Application\Rendicion;

use App\Modules\Gastos\Contracts\GastadoDeObras;
use Illuminate\Contracts\Cache\Repository;

/**
 * Cache por obra de la rendición (RN-REN-60, ADR-007): solo el agregado de lo gastado por rubro (nada que
 * dependa de quién mira). Lo presupuestado se lee en cada pedido (una consulta chica de Obras), así que
 * agregar, renombrar o quitar un presupuesto nunca deja la rendición vieja. Lo invalidan los eventos de
 * Gastos y `PresupuestoMontosCambiados` (`InvalidarRendicion`); el vencimiento es solo una red por si se
 * perdiera un evento.
 *
 * La clave lleva la generación de la obra (`rendicion:obra:{id}:g{n}`), que se lee **antes** de calcular e
 * invalidar sube: una lectura que empezó antes del commit de un gasto guarda sus totales viejos en una clave
 * que ya nadie lee, en lugar de pisar la invalidación (QC S3 M-15, RN-REN-59).
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
            $this->clave($obraId),
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
            $vigente = $this->clave($obraId);
            $this->cache->add(self::generacion($obraId), 0);
            $this->cache->increment(self::generacion($obraId));
            $this->cache->forget($vigente);
        }
    }

    /** La clave de lo gastado de la obra en su generación de hoy. */
    public function clave(int $obraId): string
    {
        $generacion = $this->cache->get(self::generacion($obraId), 0);

        return self::PREFIJO.$obraId.':g'.(is_numeric($generacion) ? (int) $generacion : 0);
    }

    private static function generacion(int $obraId): string
    {
        return self::PREFIJO.$obraId.':generacion';
    }
}
