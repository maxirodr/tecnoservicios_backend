<?php

declare(strict_types=1);

namespace App\Modules\Proveedores\Infrastructure;

use App\Modules\Proveedores\Application\Ports\AlcanceObras;
use App\Modules\Proveedores\Application\Ports\GastadoPorObra;
use App\Modules\Proveedores\Application\Ports\GastadoPorProveedor;
use App\Modules\Proveedores\Contracts\ProveedorId;

/** RN-PRV-17: hasta que Gastos registre el suyo (semana 3), "Todavía no hay gastos cargados." */
final class GastadoPorProveedorNulo implements GastadoPorProveedor
{
    public function porObra(ProveedorId $proveedor, AlcanceObras $alcance): GastadoPorObra
    {
        return GastadoPorObra::vacio();
    }
}
