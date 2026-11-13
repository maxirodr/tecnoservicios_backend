<?php

declare(strict_types=1);

namespace App\Modules\Proveedores\Application;

use App\Modules\Identidad\Contracts\Permisos;
use App\Modules\Identidad\Domain\Enums\Alcance;
use App\Modules\Identidad\Domain\Enums\ClaveFuncion;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Proveedores\Application\Ports\AlcanceObras;
use App\Modules\Proveedores\Application\Ports\GastadoPorObra;
use App\Modules\Proveedores\Application\Ports\GastadoPorProveedor;
use App\Modules\Proveedores\Contracts\ProveedorId;
use App\Modules\Proveedores\Domain\Models\Proveedor;

/**
 * F-57 · RN-PRV-15/16: "Gastado por obra" de la ficha según el alcance de `gastos` de quien mira:
 * con "solo sus obras" ve sus filas y el total de esas, sin la fila "(sin obra)".
 */
final readonly class GastadoPorObraQuery
{
    public function __construct(
        private GastadoPorProveedor $gastado,
        private Permisos $permisos,
    ) {}

    public function handle(User $user, Proveedor $proveedor): GastadoPorObra
    {
        $propias = $this->permisos->alcance($user, ClaveFuncion::Gastos->value) === Alcance::Propias;
        $alcance = $propias ? AlcanceObras::propiasDe($user->id) : AlcanceObras::todas();

        $resultado = $this->gastado->porObra(new ProveedorId($proveedor->id), $alcance);

        return $propias ? $resultado->sinFilaSinObra() : $resultado;
    }
}
