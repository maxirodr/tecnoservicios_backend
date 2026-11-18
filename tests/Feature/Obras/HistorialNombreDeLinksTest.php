<?php

declare(strict_types=1);

namespace Tests\Feature\Obras;

use App\Modules\Obras\Domain\Models\Presupuesto;
use App\Shared\Actividad\Actividad;
use App\Shared\Adjuntos\Adjunto;
use Illuminate\Database\Migrations\Migration;
use Tests\Feature\Obras\Support\ObrasTestCase;

/** PU-207: el historial nombra los links como la ficha (la última parte del link), no por el dominio. */
final class HistorialNombreDeLinksTest extends ObrasTestCase
{
    private const MIGRACION = 'database/migrations/2026_11_18_100000_nombre_de_los_links_por_la_ultima_parte.php';

    public function test_el_historial_nombra_el_link_por_la_ultima_parte_y_respeta_el_nombre_para_mostrar(): void
    {
        $laura = $this->laura();
        $oferta = $this->link($laura, 'presupuesto', 'https://drive.example/QA3-C/oferta%20tecnica%20ñandú.pdf');
        $id = $this->actingAs($laura)->postJson('/api/obras', $this->alta($laura, $oferta))->assertCreated()->json('data.id');

        $conNombre = $this->actingAs($laura)->postJson('/api/adjuntos', [
            'contexto' => 'documento_obra', 'url' => 'https://drive.example/QA3-C/certificado%201.pdf', 'nombre' => 'Certificado firmado',
        ])->assertCreated()->assertJsonPath('data.nombre', 'Certificado firmado')->json('data.id');
        $sinNombre = $this->link($laura, 'documento_obra', 'https://drive.example/QA3-C/certificado%201.pdf');
        $this->actingAs($laura)->postJson("/api/obras/{$id}/documentos", [
            'tipo' => 'certificado', 'nombre' => 'Certificado N.º 1', 'fecha' => '2026-11-18', 'adjuntos' => [$conNombre, $sinNombre],
        ])->assertCreated();

        $lineas = $this->actingAs($laura)->getJson("/api/obras/{$id}/historial")->assertOk()->json('data.*.que');

        $this->assertContains('Oferta técnica: agregado (oferta tecnica ñandú.pdf)', $lineas);
        $this->assertContains('Certificado N.º 1 · Archivo o link: agregado (certificado 1.pdf)', $lineas);
        $this->assertContains('Certificado N.º 1 · Archivo o link: agregado (Certificado firmado)', $lineas);
        $this->assertNotContains('Oferta técnica: agregado (drive.example)', $lineas);
    }

    public function test_la_migracion_renombra_los_links_guardados_con_el_dominio_y_su_historial(): void
    {
        $laura = $this->laura();
        $obra = $this->crearObra($laura);
        $presupuesto = Presupuesto::query()->where('obra_id', $obra->id)->sole();
        // Como quedaban antes del arreglo: el nombre es el dominio, también en la actividad.
        $viejo = $this->linkViejo($laura->id, $presupuesto, 'https://drive.example/QA3-REG/oc%20001.pdf', 'drive.example');
        $sinCamino = $this->linkViejo($laura->id, $presupuesto, 'https://drive.example/', 'drive.example');
        $propio = $this->linkViejo($laura->id, $presupuesto, 'https://drive.example/QA3-REG/oc-002.pdf', 'OC firmada');

        $this->migrar();
        $this->migrar(); // idempotente

        $this->assertSame('oc 001.pdf', Adjunto::query()->findOrFail($viejo)->nombre);
        $this->assertSame('drive.example', Adjunto::query()->findOrFail($sinCamino)->nombre);
        $this->assertSame('OC firmada', Adjunto::query()->findOrFail($propio)->nombre);

        $nombres = Actividad::query()->where('sujeto_type', Presupuesto::MORPH)->where('sujeto_id', $presupuesto->id)
            ->where('accion', 'adjunto_agregado')->orderBy('id')->get()
            ->map(fn (Actividad $a): mixed => ($a->cambios ?? [])[Presupuesto::CONTEXTO_APROBACION]['despues'] ?? null)->filter()->values()->all();
        $this->assertSame(['oc 001.pdf', 'drive.example', 'OC firmada'], $nombres);

        $lineas = $this->actingAs($laura)->getJson("/api/obras/{$obra->id}/historial")->json('data.*.que');
        $this->assertContains('Archivo o link de la aprobación: agregado (oc 001.pdf)', $lineas);

        $migracion = $this->migrar();
        $this->assertTrue(method_exists($migracion, 'down'));
        $migracion->down();
        $this->assertSame('drive.example', Adjunto::query()->findOrFail($viejo)->nombre);
        $this->assertSame('OC firmada', Adjunto::query()->findOrFail($propio)->nombre);
    }

    private function linkViejo(int $userId, Presupuesto $presupuesto, string $url, string $nombre): int
    {
        $adjunto = Adjunto::query()->forceCreate([
            'contexto' => Presupuesto::CONTEXTO_APROBACION,
            'tipo' => 'link',
            'url' => $url,
            'nombre' => $nombre,
            'subido_por' => $userId,
            'sujeto_type' => $presupuesto->getMorphClass(),
            'sujeto_id' => $presupuesto->id,
        ]);
        Actividad::query()->create([
            'sujeto_type' => $presupuesto->getMorphClass(),
            'sujeto_id' => $presupuesto->id,
            'accion' => 'adjunto_agregado',
            'cambios' => [Presupuesto::CONTEXTO_APROBACION => ['antes' => null, 'despues' => $nombre]],
        ]);

        return $adjunto->id;
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
