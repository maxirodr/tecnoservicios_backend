<?php

declare(strict_types=1);

namespace Tests\Feature\Obras;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Obras\Domain\Enums\ObraEstado;
use App\Modules\Obras\Domain\Models\ObraDocumento;
use App\Modules\Obras\Domain\Models\Presupuesto;
use App\Shared\Adjuntos\Adjunto;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Obras\Support\ObrasTestCase;
use Tests\Feature\Shared\Adjuntos\Support\ArchivosDePrueba;

/** HU-014 c. 3-5 · F-19 (W-20) y los contextos de adjuntos de la obra (ADR-014, RN-IDE-46/47). */
final class DocumentosYAdjuntosTest extends ObrasTestCase
{
    public function test_certificado_exige_nombre_fecha_y_un_adjunto(): void
    {
        $laura = $this->laura();
        $obra = $this->obraDe($laura);

        $this->actingAs($laura)->postJson("/api/obras/{$obra->id}/documentos", ['tipo' => 'certificado', 'adjuntos' => []])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nombre', 'fecha', 'adjuntos']);
    }

    public function test_documento_otro_no_exige_fecha_y_acepta_varios_archivos_y_links(): void
    {
        $laura = $this->laura();
        $obra = $this->obraDe($laura);
        $heic = $this->subir($laura, 'documento_obra', ArchivosDePrueba::heic());
        $link = $this->link($laura, 'documento_obra', 'https://drive.example/plano');

        $this->actingAs($laura)->postJson("/api/obras/{$obra->id}/documentos", ['tipo' => 'otro', 'nombre' => 'Plano de la nave', 'adjuntos' => [$heic, $link]])
            ->assertCreated()
            ->assertJsonPath('data.fecha', null)
            ->assertJsonPath('data.adjuntos.0.mime', 'image/heic')
            ->assertJsonPath('data.adjuntos.1.tipo', 'link');

        $this->actingAs($laura)->getJson("/api/obras/{$obra->id}")->assertJsonPath('data.documentos.0.nombre', 'Plano de la nave');
        $this->actingAs($laura)->getJson("/api/obras/{$obra->id}/historial")->assertJsonPath('data.0.que', "Agregó el documento 'Plano de la nave'");
    }

    public function test_obra_terminada_admite_certificado_final(): void
    {
        $laura = $this->laura();
        $obra = $this->obraDe($laura, ObraEstado::Terminada);
        $pdf = $this->subir($laura, 'documento_obra');

        $this->actingAs($laura)->postJson("/api/obras/{$obra->id}/documentos", ['tipo' => 'certificado', 'nombre' => 'Certificado final', 'fecha' => '2026-11-30', 'adjuntos' => [$pdf]])
            ->assertCreated()
            ->assertJsonPath('data.fecha', '2026-11-30');
    }

    public function test_documento_con_adjunto_ajeno_o_de_otro_contexto_da_422(): void
    {
        $laura = $this->laura();
        $obra = $this->obraDe($laura);
        $ajeno = $this->subir($this->diego(), 'documento_obra');
        $otroContexto = $this->subir($laura, 'presupuesto');

        foreach ([$ajeno, $otroContexto] as $id) {
            $this->actingAs($laura)->postJson("/api/obras/{$obra->id}/documentos", ['tipo' => 'otro', 'nombre' => 'Nota', 'adjuntos' => [$id]])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['adjuntos' => 'Ese adjunto no se puede usar acá.']);
        }

        $this->assertSame(0, ObraDocumento::query()->count());
    }

    public function test_editar_documento_reemplaza_la_lista_de_adjuntos(): void
    {
        $laura = $this->laura();
        $obra = $this->obraDe($laura);
        $primero = $this->subir($laura, 'documento_obra');
        $documento = $this->documento($laura, $obra->id, [$primero]);
        $segundo = $this->link($laura, 'documento_obra');

        $this->actingAs($laura)->patchJson("/api/documentos/{$documento}", ['nombre' => 'Certificado N.º 1 (corregido)', 'adjuntos' => [$segundo]])
            ->assertOk()
            ->assertJsonPath('data.nombre', 'Certificado N.º 1 (corregido)')
            ->assertJsonPath('data.adjuntos.*.id', [$segundo]);

        $this->assertNull(Adjunto::query()->find($primero));
        $this->actingAs($laura)->patchJson("/api/documentos/{$documento}", ['adjuntos' => []])->assertUnprocessable();
        $this->actingAs($laura)->patchJson("/api/documentos/{$documento}", ['fecha' => null])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['fecha' => 'El certificado lleva fecha.']);
    }

    public function test_quitar_certificado_borra_sus_archivos_y_queda_en_historial(): void
    {
        $laura = $this->laura();
        $obra = $this->obraDe($laura);
        $pdf = $this->subir($laura, 'documento_obra', ArchivosDePrueba::pdf('certificado-1.pdf'));
        $ruta = (string) $this->adjunto($pdf)->ruta;
        $documento = $this->documento($laura, $obra->id, [$pdf, $this->link($laura, 'documento_obra')]);

        $this->actingAs($laura)->deleteJson("/api/documentos/{$documento}")->assertOk();

        $this->assertSame(0, ObraDocumento::query()->count());
        $this->assertSame(0, Adjunto::query()->count());
        Storage::disk('adjuntos')->assertMissing($ruta);
        $this->actingAs($laura)->getJson("/api/obras/{$obra->id}/historial")
            ->assertJsonPath('data.0.que', "Quitó el certificado 'Certificado N.º 1' (certificado-1.pdf, 1 link)");
    }

    public function test_documentos_exigen_obras_editar_y_alcance(): void
    {
        $this->celda('Ingeniería de proyectos', 'obras', 'editar', 'propias');
        $diego = $this->diego();
        $obra = $this->obraDe($diego);
        $documento = $this->documento($diego, $obra->id, [$this->subir($diego, 'documento_obra')]);
        $carla = $this->carla();
        $laura = $this->laura();

        $this->actingAs($carla)->postJson("/api/obras/{$obra->id}/documentos", ['tipo' => 'otro', 'nombre' => 'X', 'adjuntos' => [1]])->assertForbidden();
        $this->actingAs($carla)->deleteJson("/api/documentos/{$documento}")->assertForbidden();
        $this->actingAs($laura)->patchJson("/api/documentos/{$documento}", ['nombre' => 'X'])->assertNotFound();
        $this->actingAs($laura)->deleteJson("/api/documentos/{$documento}")->assertNotFound();
        $this->actingAs($laura)->deleteJson('/api/documentos/999999')->assertNotFound();
        $this->assertSame(1, ObraDocumento::query()->count());
    }

    public function test_descarga_con_permiso_envia_attachment_y_nosniff(): void
    {
        $laura = $this->laura();
        $this->crearObra($laura);
        $oferta = Adjunto::query()->sole();

        $respuesta = $this->actingAs($this->carla())->get("/api/adjuntos/{$oferta->id}")
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertDownload('oferta-ejemplo.pdf');
        $this->assertStringStartsWith('attachment;', (string) $respuesta->headers->get('Content-Disposition'));
    }

    public function test_descarga_de_obra_ajena_con_alcance_propias_devuelve_404(): void
    {
        $this->celda('Ingeniería de proyectos', 'obras', 'ver', 'propias');
        $sofia = $this->usuarioDe('Ingeniería de proyectos', ['nombre' => 'Sofía']);
        $this->crearObra($this->diego());
        $ajena = Adjunto::query()->sole();

        $noExiste = $this->actingAs($sofia)->getJson('/api/adjuntos/999999')->assertNotFound();
        $sinPermiso = $this->actingAs($sofia)->getJson("/api/adjuntos/{$ajena->id}")->assertNotFound();
        $this->assertSame($noExiste->json(), $sinPermiso->json());
        $this->actingAs($this->sinAcceso())->getJson("/api/adjuntos/{$ajena->id}")->assertNotFound();
    }

    public function test_descarga_de_una_obra_propia_con_alcance_propias_funciona(): void
    {
        $this->celda('Ingeniería de proyectos', 'obras', 'editar', 'propias');
        $sofia = $this->usuarioDe('Ingeniería de proyectos', ['nombre' => 'Sofía']);
        $this->crearObra($sofia);
        $suya = Adjunto::query()->sole();

        $this->actingAs($sofia)->get("/api/adjuntos/{$suya->id}")->assertOk();
    }

    public function test_quitar_un_adjunto_por_la_api_comun_exige_obras_editar(): void
    {
        $laura = $this->laura();
        $obra = $this->obraDe($laura);
        $documento = $this->documento($laura, $obra->id, [$this->subir($laura, 'documento_obra'), $this->subir($laura, 'documento_obra')]);
        $adjunto = Adjunto::query()->where('sujeto_id', $documento)->firstOrFail();

        $this->actingAs($this->carla())->deleteJson("/api/adjuntos/{$adjunto->id}")->assertForbidden();
        $this->actingAs($laura)->deleteJson("/api/adjuntos/{$adjunto->id}")->assertOk();
    }

    public function test_adjunto_de_presupuesto_ya_usado_en_otra_obra_no_se_reusa(): void
    {
        $laura = $this->laura();
        $this->crearObra($laura);
        $usado = Presupuesto::query()->sole()->adjuntos()->firstOrFail();

        $this->actingAs($laura)->postJson('/api/obras', $this->alta($laura, $usado->id, ['presupuesto' => ['numero' => '2-999-2026']]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['presupuesto.oferta' => 'Ese adjunto no se puede usar acá.']);
    }

    /**
     * @param  list<int>  $adjuntos
     */
    private function documento(User $user, int $obraId, array $adjuntos): int
    {
        $id = $this->actingAs($user)->postJson("/api/obras/{$obraId}/documentos", [
            'tipo' => 'certificado', 'nombre' => 'Certificado N.º 1', 'fecha' => '2026-09-30', 'adjuntos' => $adjuntos,
        ])->assertCreated()->json('data.id');
        $this->assertIsInt($id);

        return $id;
    }
}
