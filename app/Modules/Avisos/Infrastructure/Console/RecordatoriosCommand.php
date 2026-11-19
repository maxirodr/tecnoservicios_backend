<?php

declare(strict_types=1);

namespace App\Modules\Avisos\Infrastructure\Console;

use App\Modules\Avisos\Application\Recordatorio\RecordarEntregasDeMananaAction;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/** Recordatorio "vence mañana" (F-37). Programado cada hora en `routes/console.php` (schedule:work en Supervisord). */
final class RecordatoriosCommand extends Command
{
    protected $signature = 'avisos:recordatorios';

    protected $description = 'Avisa "vence mañana" a los propietarios de pedidos abiertos con entrega mañana (desde las 8:00).';

    public function handle(RecordarEntregasDeMananaAction $recordar): int
    {
        $creados = $recordar->handle(CarbonImmutable::now());

        $this->info($creados === null
            ? 'Todavía no son las 8:00 (hora de Argentina): el recordatorio sale desde esa hora.'
            : "Recordatorios creados: {$creados}.");

        return self::SUCCESS;
    }
}
