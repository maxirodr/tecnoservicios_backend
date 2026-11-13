<?php

declare(strict_types=1);

namespace App\Modules\Obras;

use App\Modules\Identidad\Contracts\ObrasDelResponsable;
use App\Modules\Obras\Application\Acceso\ObraAdjuntos;
use App\Modules\Obras\Application\Ports\AvanceGastoDeObra;
use App\Modules\Obras\Application\Ports\PedidosDeObra;
use App\Modules\Obras\Contracts\ObrasConsulta;
use App\Modules\Obras\Domain\Models\Obra;
use App\Modules\Obras\Domain\Models\ObraDocumento;
use App\Modules\Obras\Domain\Models\Presupuesto;
use App\Modules\Obras\Infrastructure\AvanceGastoDeObraNulo;
use App\Modules\Obras\Infrastructure\ObrasConsultaEloquent;
use App\Modules\Obras\Infrastructure\ObrasDelResponsableEloquent;
use App\Modules\Obras\Infrastructure\PedidosDeObraNulo;
use App\Shared\Adjuntos\ContextosAdjuntos;
use App\Shared\Providers\ModuleServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Obras (obras.md): rutas, contratos públicos, puertos con implementación nula hasta que Pedidos
 * (semana 2) y Rendicion (semana 3) registren las suyas, y los contextos de adjuntos de la obra.
 */
final class ObrasServiceProvider extends ModuleServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        ObrasConsulta::class => ObrasConsultaEloquent::class,
        ObrasDelResponsable::class => ObrasDelResponsableEloquent::class,
    ];

    public function register(): void
    {
        // Pedidos y Rendicion registran sus implementaciones; mientras tanto, las nulas (DU-A12).
        $this->app->bindIf(PedidosDeObra::class, PedidosDeObraNulo::class);
        $this->app->bindIf(AvanceGastoDeObra::class, AvanceGastoDeObraNulo::class);
    }

    public function boot(): void
    {
        parent::boot();

        // Alias estables para `actividades.sujeto_type` y `adjuntos.sujeto_type` (antes de la primera escritura).
        Relation::morphMap([
            Obra::MORPH => Obra::class,
            Presupuesto::MORPH => Presupuesto::class,
            ObraDocumento::MORPH => ObraDocumento::class,
        ]);

        $contextos = $this->app->make(ContextosAdjuntos::class);
        $contextos->registrar(Presupuesto::CONTEXTO_OFERTA, ObraAdjuntos::class);
        $contextos->registrar(Presupuesto::CONTEXTO_APROBACION, ObraAdjuntos::class);
        $contextos->registrar(ObraDocumento::CONTEXTO, ObraAdjuntos::class);
    }

    protected function moduleDirectory(): string
    {
        return __DIR__;
    }
}
