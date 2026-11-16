<?php

declare(strict_types=1);

namespace App\Modules\Proveedores\Infrastructure\Consultas;

use App\Modules\Proveedores\Domain\NombreProveedor;
use Illuminate\Database\Eloquent\Builder;

/**
 * RN-PRV-03: por cualquier parte del nombre, sin distinguir mayúsculas, tildes, eñe ni puntuación
 * ("corralon" → "Corralón Centro"; "s.r.l" → "… SRL"; "nandu" → "Ñandú"), o por parte del CUIT con
 * o sin guiones si se escriben al menos 4 dígitos (PU-044, DU-Q05). Sin texto no filtra; si lo escrito
 * son solo signos o emojis ("%%", "--", "..") no hay nada que buscar y no trae resultados (PU-009).
 */
final readonly class PorNombre implements FiltroProveedores
{
    private const DIGITOS_MINIMOS_CUIT = 4;

    public function __construct(private ?string $texto) {}

    public function aplicar(Builder $query): Builder
    {
        if (trim($this->texto ?? '') === '') {
            return $query;
        }

        $nombre = NombreProveedor::busqueda($this->texto ?? '');
        $cuit = $this->digitosDeCuit();

        if ($nombre === '' && $cuit === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $filtro) use ($nombre, $cuit): void {
            if ($nombre !== '') {
                $filtro->orWhere('nombre_busqueda', 'like', '%'.addcslashes($nombre, '%_\\').'%');
            }

            if ($cuit !== null) {
                $filtro->orWhere('cuit', 'like', '%'.$cuit.'%');
            }
        });
    }

    /** "30-71234567", "30 7123" o "3071.2345" → sus dígitos, si son al menos 4 y no hay otra cosa. */
    private function digitosDeCuit(): ?string
    {
        $digitos = (string) preg_replace('/[\s\-.]+/', '', (string) $this->texto);

        return ctype_digit($digitos) && strlen($digitos) >= self::DIGITOS_MINIMOS_CUIT ? $digitos : null;
    }
}
