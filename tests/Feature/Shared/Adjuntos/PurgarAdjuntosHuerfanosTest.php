<?php

declare(strict_types=1);

namespace Tests\Feature\Shared\Adjuntos;

use App\Shared\Adjuntos\Adjunto;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Shared\Adjuntos\Support\AdjuntosTestCase;

/**
 * HU-025 criterio 5 · RN-IDE-45: los temporales no asociados se borran a las 24 h (fila y archivo).
 */
final class PurgarAdjuntosHuerfanosTest extends AdjuntosTestCase
{
    public function test_adjunto_huerfano_se_purga_a_las_24_horas(): void
    {
        $this->travelTo(now()->startOfMinute());
        $huerfano = $this->adjuntoSubido($this->usuario());

        $this->travel(23)->hours();
        $this->command('adjuntos:purgar-huerfanos')->assertSuccessful();
        $this->assertNotNull(Adjunto::query()->find($huerfano->id), 'Antes de las 24 h no se toca.');

        $this->travel(1)->hours();
        $this->command('adjuntos:purgar-huerfanos')->expectsOutputToContain('purgados: 1')->assertSuccessful();

        $this->assertNull(Adjunto::query()->find($huerfano->id));
        Storage::disk('adjuntos')->assertMissing((string) $huerfano->ruta);
    }

    public function test_purga_no_toca_adjuntos_asociados(): void
    {
        $this->travelTo(now()->startOfMinute());
        $asociado = $this->adjuntoDe($this->documento(), $this->usuario());

        $this->travel(48)->hours();
        $this->command('adjuntos:purgar-huerfanos')->assertSuccessful();

        $this->assertNotNull(Adjunto::query()->find($asociado->id));
        Storage::disk('adjuntos')->assertExists((string) $asociado->ruta);
    }

    public function test_la_purga_esta_programada_en_el_scheduler(): void
    {
        $comandos = collect($this->app->make(Schedule::class)->events())->map(fn ($evento): string => (string) $evento->command);

        $this->assertTrue($comandos->contains(fn (string $c): bool => str_contains($c, 'adjuntos:purgar-huerfanos')));
        $this->assertTrue($comandos->contains(fn (string $c): bool => str_contains($c, 'idempotencia:purgar')));
    }
}
