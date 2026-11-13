<?php

declare(strict_types=1);

namespace Tests\Feature\Obras\Support;

use App\Modules\Obras\Application\Ports\AvanceGastoDeObra;
use App\Modules\Obras\Application\Ports\PedidoAbiertoResumen;
use App\Modules\Obras\Application\Ports\PedidosDeObra;
use Illuminate\Contracts\Container\Container;

/**
 * Lo que en las semanas 2 y 3 implementan Pedidos y Rendicion, en memoria, para probar el contrato
 * de los puertos (obras.md §10.4).
 */
final class PuertosFalsos implements AvanceGastoDeObra, PedidosDeObra
{
    /** @var array<int, int|null> */
    public array $pedidosPorObra = [];

    /** @var list<PedidoAbiertoResumen> */
    public array $abiertos = [];

    public ?int $sinObra = null;

    /** @var array<int, numeric-string|null> */
    public array $porcentajes = [];

    /** @var array<int, bool> */
    public array $pasadas = [];

    public int $consultasDeConteo = 0;

    public int $consultasDePorcentaje = 0;

    public static function registrar(Container $app): self
    {
        $puertos = new self;
        $app->instance(PedidosDeObra::class, $puertos);
        $app->instance(AvanceGastoDeObra::class, $puertos);

        return $puertos;
    }

    public function abiertosDe(int $obraId): array
    {
        return $this->abiertos;
    }

    public function contarAbiertosPorObra(array $obraIds): array
    {
        $this->consultasDeConteo++;

        return array_map(fn (int $id): ?int => $this->pedidosPorObra[$id] ?? null, array_combine($obraIds, $obraIds));
    }

    public function contarAbiertosSinObra(): ?int
    {
        return $this->sinObra;
    }

    public function porcentajesGastados(array $obraIds): array
    {
        $this->consultasDePorcentaje++;

        return array_map(fn (int $id): ?string => $this->porcentajes[$id] ?? null, array_combine($obraIds, $obraIds));
    }

    public function pasadas(array $obraIds): array
    {
        return array_map(fn (int $id): bool => $this->pasadas[$id] ?? false, array_combine($obraIds, $obraIds));
    }
}
