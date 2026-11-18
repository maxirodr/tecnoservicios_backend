<?php

declare(strict_types=1);

namespace Tests\Feature\Obras;

use App\Modules\Obras\Domain\Models\Obra;
use App\Shared\Actividad\Actividad;
use Illuminate\Database\Migrations\Migration;
use Tests\Feature\Obras\Support\ObrasTestCase;

/** PU-208: el historial de certificados y documentos va sin comillas, como el resto (criterio de PU-128). */
final class HistorialDocumentosSinComillasTest extends ObrasTestCase
{
    private const MIGRACION = 'database/migrations/2026_11_18_100100_historial_de_documentos_quitados_sin_comillas.php';

    public function test_agregar_un_certificado_queda_sin_comillas(): void
    {
        $laura = $this->laura();
        $obra = $this->obraDe($laura);
        $link = $this->link($laura, 'documento_obra');

        $this->actingAs($laura)->postJson("/api/obras/{$obra->id}/documentos", [
            'tipo' => 'certificado', 'nombre' => 'QA3-C Certificado N.º 1', 'fecha' => '2026-11-18', 'adjuntos' => [$link],
        ])->assertCreated();

        $this->actingAs($laura)->getJson("/api/obras/{$obra->id}/historial")
            ->assertJsonPath('data.0.que', 'Agregó el certificado QA3-C Certificado N.º 1');
    }

    public function test_la_migracion_saca_las_comillas_de_los_documentos_quitados_ya_guardados(): void
    {
        $laura = $this->laura();
        $obra = $this->obraDe($laura);
        $conDetalle = $this->quitadoViejo($obra, "'Certificado N.º 1' (certificado-1.pdf, 1 link)");
        $sinDetalle = $this->quitadoViejo($obra, "'Plano (versión 2)'");

        $migracion = $this->migrar();
        $this->migrar(); // idempotente

        $this->assertSame('Certificado N.º 1 (certificado-1.pdf, 1 link)', $this->antes($conDetalle));
        $this->assertSame('Plano (versión 2)', $this->antes($sinDetalle));
        $this->actingAs($laura)->getJson("/api/obras/{$obra->id}/historial")
            ->assertJsonPath('data.1.que', 'Quitó el certificado Certificado N.º 1 (certificado-1.pdf, 1 link)');

        $this->assertTrue(method_exists($migracion, 'down'));
        $migracion->down();
        $this->assertSame("'Certificado N.º 1' (certificado-1.pdf, 1 link)", $this->antes($conDetalle));
    }

    private function quitadoViejo(Obra $obra, string $antes): int
    {
        return Actividad::query()->create([
            'sujeto_type' => $obra->getMorphClass(),
            'sujeto_id' => $obra->id,
            'accion' => 'documento_quitado',
            'cambios' => ['certificado' => ['antes' => $antes, 'despues' => null]],
        ])->id;
    }

    private function antes(int $actividad): mixed
    {
        return (Actividad::query()->findOrFail($actividad)->cambios ?? [])['certificado']['antes'] ?? null;
    }

    private function migrar(): Migration
    {
        $migracion = require base_path(self::MIGRACION);
        $this->assertInstanceOf(Migration::class, $migracion);
        $this->assertTrue(method_exists($migracion, 'up'));
        $migracion->up();

        return $migracion;
    }
}
