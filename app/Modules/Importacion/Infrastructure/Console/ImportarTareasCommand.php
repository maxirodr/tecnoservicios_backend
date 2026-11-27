<?php

declare(strict_types=1);

namespace App\Modules\Importacion\Infrastructure\Console;

use App\Modules\Importacion\Application\Corridas\CorrerCarga;
use App\Modules\Importacion\Application\Tareas\ImportarTareas;

/**
 * `importacion:tareas {archivo}` (§11.1): ensayo del lote tareas (tareas abiertas y documentos de la minuta); con
 * `--confirmar --aprobo={mail}`, la carga real. Va después del lote obras (RN-IMP-02).
 */
final class ImportarTareasCommand extends ComandoDeCarga
{
    protected $signature = 'importacion:tareas {archivo : Nombre del .xlsx dentro de la carpeta de la importación}
        {--confirmar : Guarda de verdad (sin esto es un ensayo que no guarda nada)}
        {--aprobo= : Mail de quien aprobó el informe del ensayo}';

    protected $description = 'Carga inicial de las tareas abiertas y los documentos de la minuta (ensayo por defecto)';

    public function handle(CorrerCarga $correr, ImportarTareas $lote): int
    {
        return $this->correr($correr, $lote);
    }
}
