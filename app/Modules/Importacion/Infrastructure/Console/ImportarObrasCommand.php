<?php

declare(strict_types=1);

namespace App\Modules\Importacion\Infrastructure\Console;

use App\Modules\Importacion\Application\Corridas\CorrerCarga;
use App\Modules\Importacion\Application\Obras\ImportarObras;

/**
 * `importacion:obras {archivo}` (§11.1): ensayo del lote obras; con `--confirmar --aprobo={mail}`, la carga real
 * (RN-IMP-03, 04). El archivo es un nombre dentro de la carpeta de la importación (RN-IMP-12).
 */
final class ImportarObrasCommand extends ComandoDeCarga
{
    protected $signature = 'importacion:obras {archivo : Nombre del .xlsx dentro de la carpeta de la importación}
        {--confirmar : Guarda de verdad (sin esto es un ensayo que no guarda nada)}
        {--aprobo= : Mail de quien aprobó el informe del ensayo}';

    protected $description = 'Carga inicial de obras, presupuestos, gastado, materiales y retirado (ensayo por defecto)';

    public function handle(CorrerCarga $correr, ImportarObras $lote): int
    {
        return $this->correr($correr, $lote);
    }
}
