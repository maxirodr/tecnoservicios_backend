<?php

declare(strict_types=1);

use App\Modules\Avisos\AvisosServiceProvider;
use App\Modules\Gastos\GastosServiceProvider;
use App\Modules\Identidad\IdentidadServiceProvider;
use App\Modules\Importacion\ImportacionServiceProvider;
use App\Modules\Obras\ObrasServiceProvider;
use App\Modules\Pedidos\PedidosServiceProvider;
use App\Modules\Proveedores\ProveedoresServiceProvider;
use App\Modules\Rendicion\RendicionServiceProvider;
use App\Providers\AppServiceProvider;
use App\Shared\Providers\SharedServiceProvider;

return [
    AppServiceProvider::class,
    SharedServiceProvider::class,
    IdentidadServiceProvider::class,
    ObrasServiceProvider::class,
    PedidosServiceProvider::class,
    GastosServiceProvider::class,
    RendicionServiceProvider::class,
    ProveedoresServiceProvider::class,
    AvisosServiceProvider::class,
    ImportacionServiceProvider::class,
];
