<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Modules\Proveedores\Application\Ports\AlcanceObras;
use App\Modules\Proveedores\Application\Ports\GastadoPorObra;
use App\Modules\Proveedores\Application\Ports\GastadoPorProveedor;
use App\Modules\Proveedores\Contracts\ProveedorId;

/** Puerto "Gastado por obra" falso: devuelve filas fijas y anota con qué se lo llamó. */
final class PuertoGastadoEspia implements GastadoPorProveedor
{
    public ?ProveedorId $proveedor = null;

    public ?AlcanceObras $alcance = null;

    public function __construct(private readonly GastadoPorObra $resultado) {}

    public function porObra(ProveedorId $proveedor, AlcanceObras $alcance): GastadoPorObra
    {
        $this->proveedor = $proveedor;
        $this->alcance = $alcance;

        return $this->resultado;
    }
}
