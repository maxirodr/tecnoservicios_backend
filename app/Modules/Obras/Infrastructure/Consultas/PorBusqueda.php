<?php

declare(strict_types=1);

namespace App\Modules\Obras\Infrastructure\Consultas;

use Illuminate\Database\Eloquent\Builder;

/**
 * RN-OBR-05 (F-03): el texto está en el N.º de cualquiera de sus presupuestos, en el cliente, en el
 * trabajo o en la planta. Sin distinguir mayúsculas ni tildes (collation `utf8mb4_unicode_ci`). Para
 * el N.º se ignoran los separadores: "2131", "2-131" y "2 131 2026" encuentran `2-131-2026`.
 */
final readonly class PorBusqueda implements FiltroObras
{
    private const SEPARADORES = '/[\s\-\/._]+/';

    private const NUMERO_COMPACTO = "REPLACE(REPLACE(presupuestos.numero, '-', ''), ' ', '')";

    public function __construct(private string $texto) {}

    public function aplicar(Builder $query): void
    {
        $patron = '%'.self::escapar(trim($this->texto)).'%';
        $compacto = (string) preg_replace(self::SEPARADORES, '', mb_strtoupper(trim($this->texto)));

        $query->where(function (Builder $q) use ($patron, $compacto): void {
            $q->where('obras.cliente', 'like', $patron)
                ->orWhere('obras.nombre_trabajo', 'like', $patron)
                ->orWhere('obras.planta', 'like', $patron);

            if ($compacto !== '') {
                $q->orWhereExists(fn ($sub) => $sub->selectRaw('1')
                    ->from('presupuestos')
                    ->whereColumn('presupuestos.obra_id', 'obras.id')
                    ->whereRaw(self::NUMERO_COMPACTO.' like ?', ['%'.self::escapar($compacto).'%']));
            }
        });
    }

    private static function escapar(string $texto): string
    {
        return addcslashes($texto, '\\%_');
    }
}
