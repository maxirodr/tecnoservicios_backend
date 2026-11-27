<?php

declare(strict_types=1);

namespace App\Modules\Importacion;

use App\Modules\Importacion\Application\Ports\EscritorDeInforme;
use App\Modules\Importacion\Application\Ports\EscritorDePlantilla;
use App\Modules\Importacion\Application\Ports\LectorDePlantillas;
use App\Modules\Importacion\Infrastructure\Console\CorridasCommand;
use App\Modules\Importacion\Infrastructure\Console\ImportarObrasCommand;
use App\Modules\Importacion\Infrastructure\Console\ImportarTareasCommand;
use App\Modules\Importacion\Infrastructure\Console\PlantillaCommand;
use App\Modules\Importacion\Infrastructure\Xlsx\InformeXlsx;
use App\Modules\Importacion\Infrastructure\Xlsx\LectorXlsx;
use App\Modules\Importacion\Infrastructure\Xlsx\PlantillaXlsx;
use App\Shared\Providers\ModuleServiceProvider;

/**
 * Carga inicial (importacion.md): solo comandos en el servidor, sin rutas ni permisos nuevos (RN-IMP-01). Lee y
 * escribe `.xlsx` con OpenSpout y guarda por los contratos de carga inicial de cada módulo (§11.4).
 */
final class ImportacionServiceProvider extends ModuleServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        LectorDePlantillas::class => LectorXlsx::class,
        EscritorDeInforme::class => InformeXlsx::class,
        EscritorDePlantilla::class => PlantillaXlsx::class,
    ];

    public function boot(): void
    {
        parent::boot();

        if ($this->app->runningInConsole()) {
            $this->commands([PlantillaCommand::class, ImportarObrasCommand::class, ImportarTareasCommand::class, CorridasCommand::class]);
        }
    }

    protected function moduleDirectory(): string
    {
        return __DIR__;
    }
}
