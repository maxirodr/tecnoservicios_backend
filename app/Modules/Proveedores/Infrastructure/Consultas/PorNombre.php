<?php

declare(strict_types=1);

namespace App\Modules\Proveedores\Infrastructure\Consultas;

use App\Modules\Proveedores\Domain\NombreProveedor;
use Illuminate\Database\Eloquent\Builder;

/**
 * RN-PRV-03: por cualquier parte del nombre, sin distinguir mayúsculas, tildes ni puntuación
 * ("corralon" → "Corralón Centro"; "s.r.l" → "… SRL"). Sin texto no filtra; si lo escrito son solo
 * signos o emojis ("%%", "--", "..") no hay nada que buscar y no trae resultados (PU-009).
 */
final readonly class PorNombre implements FiltroProveedores
{
    public function __construct(private ?string $texto) {}

    public function aplicar(Builder $query): Builder
    {
        if (trim($this->texto ?? '') === '') {
            return $query;
        }

        $texto = NombreProveedor::busqueda($this->texto ?? '');

        if ($texto === '') {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('nombre_busqueda', 'like', '%'.addcslashes($texto, '%_\\').'%');
    }
}
