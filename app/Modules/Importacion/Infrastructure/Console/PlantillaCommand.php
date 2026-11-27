<?php

declare(strict_types=1);

namespace App\Modules\Importacion\Infrastructure\Console;

use App\Modules\Importacion\Application\Corridas\CarpetaDeImportacion;
use App\Modules\Importacion\Application\Corridas\HojasDelLote;
use App\Modules\Importacion\Application\Ports\EscritorDePlantilla;
use App\Modules\Importacion\Domain\Lote;
use App\Modules\Importacion\Domain\Plantilla\ComoCompletar;
use Illuminate\Console\Command;

/**
 * `importacion:plantilla {obras|tareas}` (§11.1, HU-089): deja en la carpeta de la importación la plantilla vacía con
 * las hojas y columnas de §5, las listas desplegables (rubros, sectores activos, estados) y la hoja "Cómo completar".
 */
final class PlantillaCommand extends Command
{
    protected $signature = 'importacion:plantilla {lote : obras o tareas}';

    protected $description = 'Deja la plantilla vacía de la carga inicial (obras o tareas) en la carpeta de la importación';

    public function handle(CarpetaDeImportacion $carpeta, HojasDelLote $hojas, EscritorDePlantilla $escritor): int
    {
        $lote = Lote::tryFrom((string) $this->argument('lote'));

        if ($lote === null) {
            $this->error('El lote tiene que ser obras o tareas.');

            return self::FAILURE;
        }

        $ruta = $carpeta->ruta("plantilla-{$lote->value}.xlsx");
        $escritor->escribir($hojas->de($lote), ComoCompletar::de($lote), $ruta);
        $this->line("Plantilla: {$ruta}");

        return self::SUCCESS;
    }
}
