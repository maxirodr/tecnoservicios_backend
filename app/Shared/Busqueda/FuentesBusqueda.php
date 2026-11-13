<?php

declare(strict_types=1);

namespace App\Shared\Busqueda;

use Illuminate\Contracts\Container\Container;

/**
 * Registro de grupos del buscador general (`obras`, `proveedores`…) → su fuente. Los grupos salen en
 * el orden de W-18; uno que no está en ese orden va al final.
 */
final class FuentesBusqueda
{
    /** Orden de los grupos en W-18. */
    private const ORDEN = ['obras', 'pedidos', 'proveedores', 'gastos'];

    /** @var array<string, class-string<FuenteBusqueda>|FuenteBusqueda> */
    private array $fuentes = [];

    public function __construct(private readonly Container $container) {}

    /**
     * @param  class-string<FuenteBusqueda>|FuenteBusqueda  $fuente
     */
    public function registrar(string $tipo, string|FuenteBusqueda $fuente): void
    {
        $this->fuentes[$tipo] = $fuente;
    }

    /**
     * @return array<string, FuenteBusqueda> tipo => fuente, en el orden de W-18
     */
    public function enOrden(): array
    {
        $tipos = array_keys($this->fuentes);
        usort($tipos, static fn (string $uno, string $otro): int => self::posicion($uno) <=> self::posicion($otro));

        $fuentes = [];
        foreach ($tipos as $tipo) {
            $fuentes[$tipo] = $this->resolver($tipo);
        }

        return $fuentes;
    }

    private function resolver(string $tipo): FuenteBusqueda
    {
        $fuente = $this->fuentes[$tipo];

        if (is_string($fuente)) {
            $fuente = $this->container->make($fuente);
            $this->fuentes[$tipo] = $fuente;
        }

        return $fuente;
    }

    private static function posicion(string $tipo): int
    {
        $posicion = array_search($tipo, self::ORDEN, true);

        return $posicion === false ? count(self::ORDEN) : $posicion;
    }
}
