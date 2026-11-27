<?php

declare(strict_types=1);

namespace App\Modules\Importacion\Application\Obras;

use App\Modules\Importacion\Domain\Informe\Aviso;
use App\Modules\Importacion\Domain\Informe\Faltante;
use App\Modules\Obras\Contracts\ObraInicial;
use App\Modules\Obras\Contracts\PresupuestoInicial;

/**
 * Una obra del lote obras con todo lo suyo, ya leído de las cinco hojas (§5.1…§5.5): los datos para crearla (si no
 * está en el sistema), su gastado por rubro del sistema, su lista de materiales con lo retirado y **todos** sus
 * faltantes juntos (RN-IMP-19). Con un faltante no entra nada de ella.
 */
final class ObraDelLote
{
    /** @var list<Faltante> */
    public array $faltantes = [];

    /** @var list<Aviso> */
    public array $avisos = [];

    /** @var list<PresupuestoInicial> el principal primero */
    public array $presupuestos = [];

    /** @var array<string, numeric-string> rubro del sistema (clave) => importe (los rubros en 0 no están) */
    public array $gastado = [];

    /** @var array<string, list<string>> rubro del sistema (clave) => de qué filas viene ("INSUMO 80.000") */
    public array $origenDelGastado = [];

    /** @var array<string, list<string>> quién aprobó => ["INSUMO → INSUMOS"] (RN-IMP-17) */
    public array $equivalencias = [];

    /** @var list<MaterialDelLote> */
    public array $materiales = [];

    public bool $tieneGastado = false;

    /**
     * Los datos de la hoja Obras para el alta, ya revisados (vacío si la obra ya está o le falta algo).
     *
     * @var array{trabajo: string, cliente: string, planta: ?string, responsable: int, en_ejecucion: bool, inicio: ?string, fin: ?string, drive: ?string, contactos: array{solicitante: array{nombre: string, email: string, telefono: string}, comprador: array{nombre: string, email: string, telefono: string}}}|array{}
     */
    public array $alta = [];

    public ?string $corte = null;

    /** @var numeric-string|null */
    public ?string $totalPlanilla = null;

    public function __construct(
        public readonly string $numero,
        public readonly int $fila,
        public readonly string $trabajo,
        public readonly string $estado,
        public readonly ?int $existente,
    ) {}

    /**
     * @param  list<Faltante>  $faltantes
     */
    public function faltan(array $faltantes): void
    {
        array_push($this->faltantes, ...$faltantes);
    }

    public function entra(): bool
    {
        return $this->faltantes === [];
    }

    /** Lo que Obras necesita para el alta (RN-IMP-18); null si la obra ya está en el sistema o le falta algo. */
    public function datos(): ?ObraInicial
    {
        if ($this->alta === [] || $this->presupuestos === [] || ! $this->entra()) {
            return null;
        }

        $a = $this->alta;

        return new ObraInicial(
            $a['trabajo'], $a['cliente'], $a['planta'], $a['responsable'], $a['en_ejecucion'], $a['inicio'], $a['fin'], $a['drive'],
            $this->presupuestos, $a['contactos'],
        );
    }

    /** @return numeric-string */
    public function gastadoTotal(): string
    {
        return array_reduce($this->gastado, static fn (string $suma, string $importe): string => bcadd($suma, $importe, 2), '0.00');
    }

    /** @return numeric-string */
    public function presupuestadoTotal(): string
    {
        $total = '0.00';

        foreach ($this->presupuestos as $p) {
            foreach ($p->total === null ? $p->montos : [$p->total] as $importe) {
                $total = bcadd($total, $importe, 2);
            }
        }

        return $total;
    }

    public function conRetirado(): int
    {
        return count(array_filter($this->materiales, static fn (MaterialDelLote $m): bool => $m->conRetirado()));
    }
}
