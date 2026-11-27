<?php

declare(strict_types=1);

namespace App\Modules\Importacion\Infrastructure\Console;

use App\Modules\Importacion\Application\Corridas\RegistroDeCorridas;
use App\Modules\Importacion\Infrastructure\Models\Corrida;
use Illuminate\Console\Command;

/** `importacion:corridas` (§11.1): fecha, lote, modo, huella corta, aprobó, estado y conteos de cada corrida. */
final class CorridasCommand extends Command
{
    private const LARGO_HUELLA = 12;

    protected $signature = 'importacion:corridas';

    protected $description = 'Lista las corridas de la carga inicial (ensayos y cargas confirmadas)';

    public function handle(RegistroDeCorridas $corridas): int
    {
        $this->table(
            ['Fecha', 'Lote', 'Modo', 'Huella', 'Aprobó', 'Estado', 'Conteos'],
            array_map(static fn (Corrida $c): array => [
                $c->created_at->setTimezone(config()->string('app.zona_empresa'))->format('d/m/Y H:i'),
                $c->lote->value,
                $c->modo->value,
                substr($c->huella, 0, self::LARGO_HUELLA),
                $c->aprobo->nombre ?? '—',
                $c->estado->value,
                implode(' · ', array_map(static fn (string $k, int $v): string => "{$k} {$v}", array_keys($c->resumen ?? []), $c->resumen ?? [])),
            ], $corridas->todas()),
        );

        return self::SUCCESS;
    }
}
