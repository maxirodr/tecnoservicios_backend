<?php

declare(strict_types=1);

namespace App\Modules\Proveedores;

use App\Modules\Proveedores\Application\Ports\GastadoPorProveedor;
use App\Modules\Proveedores\Contracts\ProveedoresConsulta;
use App\Modules\Proveedores\Infrastructure\GastadoPorProveedorNulo;
use App\Modules\Proveedores\Infrastructure\ProveedoresConsultaEloquent;
use App\Modules\Proveedores\Infrastructure\ProveedoresEnBuscador;
use App\Shared\Busqueda\FuentesBusqueda;
use App\Shared\Providers\ModuleServiceProvider;

final class ProveedoresServiceProvider extends ModuleServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        ProveedoresConsulta::class => ProveedoresConsultaEloquent::class,
    ];

    public function register(): void
    {
        // RN-PRV-17: Gastos registra el suyo en la semana 3.
        $this->app->bindIf(GastadoPorProveedor::class, GastadoPorProveedorNulo::class);
    }

    public function boot(): void
    {
        parent::boot();

        $this->app->make(FuentesBusqueda::class)->registrar(ProveedoresEnBuscador::TIPO, ProveedoresEnBuscador::class);
    }

    protected function moduleDirectory(): string
    {
        return __DIR__;
    }
}
