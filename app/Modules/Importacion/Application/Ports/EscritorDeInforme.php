<?php

declare(strict_types=1);

namespace App\Modules\Importacion\Application\Ports;

use App\Modules\Importacion\Domain\Informe\Informe;

/** El informe `.xlsx` de cada corrida, con las hojas de `importacion.md` §5.8. */
interface EscritorDeInforme
{
    public function escribir(Informe $informe, string $ruta): void;
}
