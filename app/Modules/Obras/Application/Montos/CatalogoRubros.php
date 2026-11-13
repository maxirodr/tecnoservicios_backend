<?php

declare(strict_types=1);

namespace App\Modules\Obras\Application\Montos;

use App\Modules\Rendicion\Domain\Models\Rubro;
use Illuminate\Database\Eloquent\Collection;

/**
 * Lista cerrada de rubros (obras.md §4.6), en el orden de su hoja "General". Se lee una vez por
 * pedido: el catálogo no cambia desde pantallas en la Etapa 1.
 */
final class CatalogoRubros
{
    /** @var Collection<int, Rubro>|null */
    private ?Collection $rubros = null;

    /**
     * @return Collection<int, Rubro>
     */
    public function todos(): Collection
    {
        return $this->rubros ??= Rubro::query()->orderBy('orden')->get();
    }

    /**
     * Los 12 que se presupuestan (W-13): todos menos EPP.
     *
     * @return Collection<int, Rubro>
     */
    public function presupuestables(): Collection
    {
        return $this->todos()->filter(static fn (Rubro $r): bool => $r->se_presupuesta)->values();
    }

    /**
     * @return list<string>
     */
    public function clavesPresupuestables(): array
    {
        return array_values(array_map(static fn (Rubro $r): string => $r->clave, $this->presupuestables()->all()));
    }
}
