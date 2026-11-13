<?php

declare(strict_types=1);

namespace App\Shared\Idempotencia;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Las Idempotency-Key valen 24 h (ADR-011). Programado cada hora (routes/console.php).
 */
final class PurgarIdempotencyKeysCommand extends Command
{
    protected $signature = 'idempotencia:purgar';

    protected $description = 'Borra las Idempotency-Key con más de 24 h.';

    public function handle(): int
    {
        $borradas = IdempotencyKey::query()
            ->where('created_at', '<=', Carbon::now()->subHours(IdempotencyKey::HORAS_VIGENCIA))
            ->delete();

        $this->info("Idempotency-Key purgadas: {$borradas}");

        return self::SUCCESS;
    }
}
