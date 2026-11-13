<?php

declare(strict_types=1);

namespace Tests\Feature\Shared\Adjuntos;

use App\Modules\Identidad\Domain\Enums\Alcance;
use App\Modules\Identidad\Domain\Enums\Nivel;
use App\Shared\Adjuntos\Adjunto;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Shared\Adjuntos\Support\AdjuntosTestCase;

/**
 * HU-025 criterio 6 · RN-IDE-47: bajar exige ver el recurso dueño; sin permiso o id ajeno → 404.
 */
final class DescargarAdjuntoTest extends AdjuntosTestCase
{
    public function test_descarga_con_permiso_envia_attachment_y_nosniff(): void
    {
        $lector = $this->conObras($this->usuario(), Nivel::Ver);
        $adjunto = $this->adjuntoDe($this->documento(), $this->usuario());

        $response = $this->actingAs($lector)->get('/api/adjuntos/'.$adjunto->id);

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertDownload('oferta-ejemplo.pdf');
        $this->assertStringStartsWith('attachment;', (string) $response->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF-', $response->streamedContent());
    }

    public function test_descarga_sin_permiso_de_ver_el_dueno_devuelve_404(): void
    {
        $sinAcceso = $this->usuario();
        $adjunto = $this->adjuntoDe($this->documento(), $this->usuario());

        $this->actingAs($sinAcceso)->getJson('/api/adjuntos/'.$adjunto->id)
            ->assertNotFound()
            ->assertJsonPath('data', null);
    }

    public function test_descarga_respeta_alcance_solo_sus_obras(): void
    {
        $ingeniera = $this->conObras($this->usuario(), Nivel::Editar, Alcance::Propias);
        $suya = $this->adjuntoDe($this->documento($ingeniera), $this->usuario());

        $this->actingAs($ingeniera)->get('/api/adjuntos/'.$suya->id)->assertOk();
    }

    public function test_descarga_de_obra_ajena_con_alcance_propias_devuelve_404(): void
    {
        $ingeniera = $this->conObras($this->usuario(), Nivel::Ver, Alcance::Propias);
        $ajena = $this->adjuntoDe($this->documento($this->usuario()), $this->usuario());

        $noExiste = $this->actingAs($ingeniera)->getJson('/api/adjuntos/999999')->assertNotFound();
        $sinPermiso = $this->actingAs($ingeniera)->getJson('/api/adjuntos/'.$ajena->id)->assertNotFound();

        $this->assertSame($noExiste->json(), $sinPermiso->json(), 'No se confirma que el adjunto exista.');
    }

    public function test_adjunto_temporal_solo_lo_baja_quien_lo_subio(): void
    {
        $autora = $this->usuario();
        $temporal = $this->adjuntoSubido($autora);
        $otro = $this->conObras($this->usuario(), Nivel::Editar);

        $this->actingAs($autora)->get('/api/adjuntos/'.$temporal->id)->assertOk();
        $this->actingAs($otro)->getJson('/api/adjuntos/'.$temporal->id)->assertNotFound();
    }

    public function test_descarga_de_link_devuelve_404(): void
    {
        $autora = $this->usuario();
        $id = $this->actingAs($autora)
            ->postJson('/api/adjuntos', ['contexto' => self::CONTEXTO, 'url' => 'https://drive.example/x'])
            ->json('data.id');

        $this->actingAs($autora)->getJson('/api/adjuntos/'.$id)->assertNotFound();
    }

    public function test_descarga_sin_sesion_devuelve_401(): void
    {
        $adjunto = $this->adjuntoSubido($this->usuario());
        $this->app->make('auth')->forgetGuards();

        $this->getJson('/api/adjuntos/'.$adjunto->id)->assertUnauthorized();
    }

    public function test_archivo_que_falta_en_disco_devuelve_404(): void
    {
        $autora = $this->usuario();
        $adjunto = $this->adjuntoSubido($autora);
        Storage::disk('adjuntos')->delete((string) Adjunto::query()->findOrFail($adjunto->id)->ruta);

        $this->actingAs($autora)->getJson('/api/adjuntos/'.$adjunto->id)->assertNotFound();
    }
}
