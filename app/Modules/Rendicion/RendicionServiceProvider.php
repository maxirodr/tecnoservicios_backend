<?php

declare(strict_types=1);

namespace App\Modules\Rendicion;

use App\Modules\Rendicion\Contracts\CatalogoDeRubros;
use App\Modules\Rendicion\Contracts\MaterialesDeObra;
use App\Modules\Rendicion\Domain\Models\Material;
use App\Modules\Rendicion\Infrastructure\CatalogoDeRubrosEloquent;
use App\Modules\Rendicion\Infrastructure\MaterialesDeObraNulo;
use App\Shared\Providers\ModuleServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;

final class RendicionServiceProvider extends ModuleServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        CatalogoDeRubros::class => CatalogoDeRubrosEloquent::class,
    ];

    public function register(): void
    {
        // Rendicion cambia la nula por la lista real de W-48 (semana 3, rendicion.md §12.4.3).
        $this->app->bindIf(MaterialesDeObra::class, MaterialesDeObraNulo::class);
    }

    public function boot(): void
    {
        parent::boot();

        Relation::morphMap([Material::MORPH => Material::class]);
    }

    protected function moduleDirectory(): string
    {
        return __DIR__;
    }
}
