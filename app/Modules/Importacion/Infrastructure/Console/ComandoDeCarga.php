<?php

declare(strict_types=1);

namespace App\Modules\Importacion\Infrastructure\Console;

use App\Modules\Importacion\Application\Corridas\CorrerCarga;
use App\Modules\Importacion\Application\Corridas\ImportadorDeLote;
use Illuminate\Console\Command;

/** Lo común de `importacion:obras` y `importacion:tareas`: solo conteos, huella corta y ruta (RN-IMP-13). */
abstract class ComandoDeCarga extends Command
{
    protected function correr(CorrerCarga $correr, ImportadorDeLote $lote): int
    {
        $aprobo = $this->option('aprobo');
        $salida = $correr->handle(
            $lote,
            (string) $this->argument('archivo'),
            (bool) $this->option('confirmar'),
            is_string($aprobo) ? $aprobo : null,
        );

        foreach ($salida->lineas as $linea) {
            $salida->exito ? $this->line($linea) : $this->error($linea);
        }

        if ($salida->huellaCorta !== null) {
            $this->line("Huella: {$salida->huellaCorta}");
        }

        return $salida->exito ? self::SUCCESS : self::FAILURE;
    }
}
