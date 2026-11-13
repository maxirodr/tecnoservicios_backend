<?php

declare(strict_types=1);

namespace App\Shared\Adjuntos\Application;

use App\Shared\Adjuntos\Adjunto;
use App\Shared\Adjuntos\AdjuntosService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * Borra los adjuntos temporales (sin recurso dueño) con más de `adjuntos.horas_huerfano` horas:
 * fila y archivo físico (RN-IDE-45). Los asociados no se tocan.
 */
final readonly class PurgarAdjuntosHuerfanosAction
{
    private const LOTE = 200;

    public function __construct(private AdjuntosService $adjuntos) {}

    /** @return int cantidad de adjuntos purgados */
    public function handle(): int
    {
        $limite = Carbon::now()->subHours((int) config('adjuntos.horas_huerfano'));
        $purgados = 0;

        Adjunto::query()
            ->whereNull('sujeto_id')
            ->where('created_at', '<=', $limite)
            ->chunkById(self::LOTE, function (Collection $lote) use (&$purgados): void {
                foreach ($lote as $adjunto) {
                    // Si justo se asoció mientras corría la purga, no se borra (ni su archivo).
                    if (Adjunto::query()->whereKey($adjunto->id)->whereNull('sujeto_id')->delete() === 1) {
                        $this->adjuntos->borrarArchivos(new Collection([$adjunto]));
                        $purgados++;
                    }
                }
            });

        return $purgados;
    }
}
