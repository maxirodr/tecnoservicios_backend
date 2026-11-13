<?php

declare(strict_types=1);

namespace App\Modules\Proveedores\Application;

use App\Modules\Proveedores\Domain\Cuit;
use App\Modules\Proveedores\Domain\Models\Proveedor;
use App\Modules\Proveedores\Domain\NombreProveedor;
use App\Modules\Proveedores\Domain\SimilitudDeNombres;
use Illuminate\Database\Eloquent\Builder;

/**
 * RN-PRV-08/09: "Parecidos ya cargados" mientras se escribe el nombre o el CUIT, al dar de alta, al
 * editar (sin incluirse a sí mismo) y en el alta rápida. No bloquea. Hasta 5, los más parecidos
 * primero; el mismo CUIT va antes que cualquier nombre.
 */
final class ProveedoresParecidosQuery
{
    private const MAXIMO = 5;

    /** El mismo CUIT es más fuerte que cualquier parecido de nombre (puntaje máximo 1). */
    private const PUNTAJE_CUIT = 2.0;

    private const COLUMNAS = ['id', 'nombre', 'nombre_normalizado', 'cuit'];

    /**
     * @return list<Parecido>
     */
    public function handle(?string $nombre, ?string $cuit, ?int $excluirId): array
    {
        /** @var array<int, Parecido> $parecidos */
        $parecidos = [];
        $digitos = $cuit === null ? null : Cuit::desde($cuit)?->digitos;

        if ($digitos !== null) {
            foreach ($this->candidatos($excluirId)->where('cuit', $digitos)->get() as $proveedor) {
                $parecidos[$proveedor->id] = new Parecido($proveedor, Parecido::MOTIVO_CUIT, self::PUNTAJE_CUIT);
            }
        }

        $normalizado = NombreProveedor::normalizado($nombre ?? '');
        if ($normalizado !== '') {
            foreach ($this->candidatos($excluirId)->lazyById() as $proveedor) {
                if (! isset($parecidos[$proveedor->id]) && SimilitudDeNombres::sonParecidos($normalizado, $proveedor->nombre_normalizado)) {
                    $puntaje = SimilitudDeNombres::puntaje($normalizado, $proveedor->nombre_normalizado);
                    $parecidos[$proveedor->id] = new Parecido($proveedor, Parecido::MOTIVO_NOMBRE, $puntaje);
                }
            }
        }

        usort($parecidos, static fn (Parecido $uno, Parecido $otro): int => [$otro->puntaje, $uno->proveedor->nombre] <=> [$uno->puntaje, $otro->proveedor->nombre]);

        return array_slice($parecidos, 0, self::MAXIMO);
    }

    /**
     * @return Builder<Proveedor>
     */
    private function candidatos(?int $excluirId): Builder
    {
        return Proveedor::query()
            ->select(self::COLUMNAS)
            ->when($excluirId !== null, fn (Builder $query): Builder => $query->whereKeyNot($excluirId));
    }
}
