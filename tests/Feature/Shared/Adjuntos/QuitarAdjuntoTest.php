<?php

declare(strict_types=1);

namespace Tests\Feature\Shared\Adjuntos;

use App\Modules\Identidad\Domain\Enums\Alcance;
use App\Modules\Identidad\Domain\Enums\Nivel;
use App\Shared\Actividad\Actividad;
use App\Shared\Adjuntos\Adjunto;
use App\Shared\Adjuntos\AdjuntosService;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Shared\Adjuntos\Support\AdjuntosTestCase;

/**
 * HU-025 criterio 7 · RN-IDE-48: quitar exige editar el dueño, borra el archivo y deja actividad.
 */
final class QuitarAdjuntoTest extends AdjuntosTestCase
{
    public function test_quitar_adjunto_borra_el_archivo_y_queda_en_actividades(): void
    {
        $editora = $this->conObras($this->usuario(), Nivel::Editar);
        $documento = $this->documento();
        $adjunto = $this->adjuntoDe($documento, $this->usuario());
        $ruta = (string) $adjunto->ruta;

        $this->actingAs($editora)->deleteJson('/api/adjuntos/'.$adjunto->id)
            ->assertOk()
            ->assertJsonPath('data', null)
            ->assertJsonPath('message', 'Adjunto quitado.');

        $this->assertNull(Adjunto::query()->find($adjunto->id));
        Storage::disk('adjuntos')->assertMissing($ruta);

        $actividad = Actividad::query()->sole();
        $this->assertSame(AdjuntosService::ACCION_QUITADO, $actividad->accion);
        $this->assertSame($editora->id, $actividad->user_id);
        $this->assertTrue($documento->is($actividad->sujeto()->first()));
        $this->assertSame([self::CONTEXTO => ['antes' => 'oferta-ejemplo.pdf', 'despues' => null]], $actividad->cambios);
        $this->assertStringNotContainsString($ruta, (string) json_encode($actividad->cambios));
    }

    public function test_quitar_con_obras_ver_devuelve_403_y_no_borra(): void
    {
        $lector = $this->conObras($this->usuario(), Nivel::Ver);
        $adjunto = $this->adjuntoDe($this->documento(), $this->usuario());

        $this->actingAs($lector)->deleteJson('/api/adjuntos/'.$adjunto->id)->assertForbidden();

        $this->assertNotNull(Adjunto::query()->find($adjunto->id));
        Storage::disk('adjuntos')->assertExists((string) $adjunto->ruta);
    }

    public function test_quitar_adjunto_de_obra_ajena_con_alcance_propias_devuelve_404(): void
    {
        $ingeniera = $this->conObras($this->usuario(), Nivel::Editar, Alcance::Propias);
        $ajeno = $this->adjuntoDe($this->documento($this->usuario()), $this->usuario());

        $this->actingAs($ingeniera)->deleteJson('/api/adjuntos/'.$ajeno->id)->assertNotFound();

        $this->assertNotNull(Adjunto::query()->find($ajeno->id));
    }

    public function test_quien_lo_subio_quita_su_temporal_y_nadie_mas_puede(): void
    {
        $autora = $this->usuario();
        $temporal = $this->adjuntoSubido($autora);

        $this->actingAs($this->conObras($this->usuario(), Nivel::Editar))
            ->deleteJson('/api/adjuntos/'.$temporal->id)
            ->assertNotFound();

        $this->actingAs($autora)->deleteJson('/api/adjuntos/'.$temporal->id)->assertOk();

        $this->assertNull(Adjunto::query()->find($temporal->id));
        Storage::disk('adjuntos')->assertMissing((string) $temporal->ruta);
        $this->assertSame(0, Actividad::query()->count(), 'Un temporal no tiene recurso dueño: no hay historial.');
    }

    public function test_service_quitar_rechaza_adjunto_de_otro_recurso(): void
    {
        $adjunto = $this->adjuntoDe($this->documento(), $this->usuario());

        $this->expectExceptionMessage(AdjuntosService::MENSAJE_NO_UTILIZABLE);

        $this->app->make(AdjuntosService::class)->quitar([$adjunto->id], $this->documento());
    }

    public function test_quitar_sin_sesion_devuelve_401(): void
    {
        $this->deleteJson('/api/adjuntos/1')->assertUnauthorized();
    }
}
