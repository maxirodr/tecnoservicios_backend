<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Application;

use App\Modules\Rendicion\Contracts\CatalogoDeRubros;
use App\Modules\Rendicion\Contracts\RubroDelCatalogo;

/**
 * La lista cerrada de rubros leída por `CatalogoDeRubros` (gastos.md §12.4: Gastos no usa el modelo de
 * Rendicion), indexada por id y por clave. Los 11 que se imputan en una factura son los 13 sin los de
 * carga por período (RN-GAS-12).
 */
final class Rubros
{
    public const AMORTIZACION = 'amortizacion';

    /** @var array<int, RubroDelCatalogo>|null */
    private ?array $indice = null;

    public function __construct(private readonly CatalogoDeRubros $catalogo) {}

    /**
     * @return list<RubroDelCatalogo> los 13, en el orden de su General
     */
    public function todos(): array
    {
        return array_values($this->indice());
    }

    public function porClave(string $clave): ?RubroDelCatalogo
    {
        foreach ($this->indice() as $rubro) {
            if ($rubro->clave === $clave) {
                return $rubro;
            }
        }

        return null;
    }

    public function porId(?int $id): ?RubroDelCatalogo
    {
        return $id === null ? null : ($this->indice()[$id] ?? null);
    }

    public function idDe(string $clave): ?int
    {
        return $this->porClave($clave)?->id;
    }

    /**
     * @param  list<string>  $claves
     * @return list<int>
     */
    public function idsDe(array $claves): array
    {
        return array_values(array_filter(array_map($this->idDe(...), $claves), static fn (?int $id): bool => $id !== null));
    }

    /**
     * @return array<int, RubroDelCatalogo>
     */
    private function indice(): array
    {
        if ($this->indice === null) {
            $this->indice = [];

            foreach ($this->catalogo->imputables() as $rubro) {
                $this->indice[$rubro->id] = $rubro;
            }
        }

        return $this->indice;
    }
}
