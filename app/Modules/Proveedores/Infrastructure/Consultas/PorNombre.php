<?php

declare(strict_types=1);

namespace App\Modules\Proveedores\Infrastructure\Consultas;

use App\Modules\Proveedores\Domain\NombreProveedor;
use Illuminate\Database\Eloquent\Builder;

/**
 * RN-PRV-03: por cualquier parte del nombre, sin distinguir mayúsculas, tildes ni puntuación
 * ("corralon" → "Corralón Centro"; "s.r.l" → "… SRL"). Sin texto útil no filtra.
 */
final readonly class PorNombre implements FiltroProveedores
{
    public function __construct(private ?string $texto) {}

    public function aplicar(Builder $query): Builder
    {
        $texto = NombreProveedor::busqueda($this->texto ?? '');

        if ($texto === '') {
            return $query;
        }

        return $query->where('nombre_busqueda', 'like', '%'.addcslashes($texto, '%_\\').'%');
    }
}
