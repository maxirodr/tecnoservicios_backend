<?php

declare(strict_types=1);

namespace App\Shared\Adjuntos\Console;

use App\Shared\Adjuntos\Application\PurgarAdjuntosHuerfanosAction;
use Illuminate\Console\Command;

/**
 * Programado cada hora (routes/console.php, corre con `schedule:work` bajo Supervisord).
 */
final class PurgarAdjuntosHuerfanosCommand extends Command
{
    protected $signature = 'adjuntos:purgar-huerfanos';

    protected $description = 'Borra los adjuntos temporales no asociados con más de 24 h (fila y archivo).';

    public function handle(PurgarAdjuntosHuerfanosAction $purgar): int
    {
        $this->info("Adjuntos huérfanos purgados: {$purgar->handle()}");

        return self::SUCCESS;
    }
}
