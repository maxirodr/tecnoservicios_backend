<?php

declare(strict_types=1);

namespace App\Modules\Proveedores\Application\Ports;

use App\Modules\Proveedores\Contracts\ProveedorId;

/**
 * Puerto de F-57 (proveedores.md §10.3): lo implementa Gastos en la semana 3 (agregado sobre
 * `gasto_renglones` de gastos no anulados, por `obra_id`, null = sin obra). Semana 1: el nulo.
 */
interface GastadoPorProveedor
{
    public function porObra(ProveedorId $proveedor, AlcanceObras $alcance): GastadoPorObra;
}
