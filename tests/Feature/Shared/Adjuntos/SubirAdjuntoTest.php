<?php

declare(strict_types=1);

namespace Tests\Feature\Shared\Adjuntos;

use App\Shared\Adjuntos\Adjunto;
use App\Shared\Adjuntos\DetectorTipoArchivo;
use App\Shared\Adjuntos\Http\Rules\LinkHttp;
use App\Shared\Adjuntos\Http\Rules\TipoPermitidoPorContenido;
use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Shared\Adjuntos\Support\AdjuntosTestCase;
use Tests\Feature\Shared\Adjuntos\Support\ArchivosDePrueba;

/**
 * HU-025 criterios 1 a 4 · RN-IDE-45 · obras.md §4.8 y §10.5.
 */
final class SubirAdjuntoTest extends AdjuntosTestCase
{
    public function test_usuario_autenticado_sube_adjunto_y_recibe_id_temporal(): void
    {
        $user = $this->usuario();

        $response = $this->subir($user, ArchivosDePrueba::pdf())->assertCreated();

        $adjunto = $this->adjuntoDeLaRespuesta($response);
        $this->assertTrue($adjunto->esTemporal());
        $this->assertSame($user->id, $adjunto->subido_por);
        $response->assertJsonPath('data.subido_por.id', $user->id)
            ->assertJsonPath('data.contexto', 'documento_obra')
            ->assertJsonPath('message', null)
            ->assertJsonPath('errors', null);
    }

    public function test_sin_sesion_no_sube_adjuntos(): void
    {
        $this->postJson('/api/adjuntos', ['contexto' => self::CONTEXTO, 'archivo' => ArchivosDePrueba::pdf()])
            ->assertUnauthorized()
            ->assertJsonPath('data', null);

        $this->assertSame(0, Adjunto::query()->count());
    }

    public function test_sube_pdf_valido_y_devuelve_id_temporal(): void
    {
        $response = $this->subir($this->usuario(), ArchivosDePrueba::pdf('oferta-ejemplo.pdf'))->assertCreated();

        $response->assertJsonPath('data.tipo', 'archivo')
            ->assertJsonPath('data.nombre', 'oferta-ejemplo.pdf')
            ->assertJsonPath('data.mime', 'application/pdf')
            ->assertJsonPath('data.url', null)
            ->assertJsonPath('data.descarga', '/api/adjuntos/'.$response->json('data.id'));
        $this->assertGreaterThan(0, $response->json('data.tamano_bytes'));

        $adjunto = $this->adjuntoDeLaRespuesta($response);
        $this->assertMatchesRegularExpression('#^adjuntos/\d{4}/\d{2}/[0-9a-f-]{36}$#', (string) $adjunto->ruta);
        Storage::disk('adjuntos')->assertExists((string) $adjunto->ruta);
    }

    public function test_sube_foto_heic_del_celular(): void
    {
        $this->subir($this->usuario(), ArchivosDePrueba::heic())
            ->assertCreated()
            ->assertJsonPath('data.mime', 'image/heic')
            ->assertJsonPath('data.nombre', 'certificado-1.heic');
    }

    /**
     * @param  Closure(): UploadedFile  $archivo
     */
    #[DataProvider('tiposAceptados')]
    public function test_acepta_imagenes_excel_word_csv_y_txt_por_contenido(Closure $archivo, string $mime): void
    {
        $this->subir($this->usuario(), $archivo())->assertCreated()->assertJsonPath('data.mime', $mime);
    }

    /**
     * @return array<string, array{Closure(): UploadedFile, string}>
     */
    public static function tiposAceptados(): array
    {
        return [
            'jpg' => [ArchivosDePrueba::jpg(...), 'image/jpeg'],
            'png' => [ArchivosDePrueba::png(...), 'image/png'],
            'webp' => [ArchivosDePrueba::webp(...), 'image/webp'],
            'xlsx' => [ArchivosDePrueba::xlsx(...), DetectorTipoArchivo::XLSX],
            'docx' => [ArchivosDePrueba::docx(...), DetectorTipoArchivo::DOCX],
            'xls' => [ArchivosDePrueba::xls(...), DetectorTipoArchivo::XLS],
            'doc' => [ArchivosDePrueba::doc(...), DetectorTipoArchivo::DOC],
            'csv' => [ArchivosDePrueba::csv(...), 'text/csv'],
            'txt' => [ArchivosDePrueba::txt(...), 'text/plain'],
        ];
    }

    public function test_registra_link_https(): void
    {
        $this->actingAs($this->usuario())
            ->postJson('/api/adjuntos', ['contexto' => self::CONTEXTO, 'url' => 'https://drive.example/oc-ejemplo'])
            ->assertCreated()
            ->assertJsonPath('data.tipo', 'link')
            ->assertJsonPath('data.url', 'https://drive.example/oc-ejemplo')
            ->assertJsonPath('data.nombre', 'drive.example')
            ->assertJsonPath('data.descarga', null)
            ->assertJsonPath('data.mime', null);
    }

    public function test_link_con_etiqueta_guarda_la_etiqueta(): void
    {
        $this->actingAs($this->usuario())
            ->postJson('/api/adjuntos', ['contexto' => self::CONTEXTO, 'url' => 'http://drive.example/x', 'nombre' => 'OC firmada'])
            ->assertCreated()
            ->assertJsonPath('data.nombre', 'OC firmada');
    }

    public function test_rechaza_ejecutable_renombrado_a_pdf_por_contenido(): void
    {
        $this->subir($this->usuario(), ArchivosDePrueba::exeRenombradoAPdf())
            ->assertUnprocessable()
            ->assertJsonPath('errors.archivo.0', TipoPermitidoPorContenido::MENSAJE);

        $this->assertSame(0, Adjunto::query()->count());
        $this->assertSame([], Storage::disk('adjuntos')->allFiles());
    }

    public function test_rechaza_html_y_svg(): void
    {
        $user = $this->usuario();

        foreach ([ArchivosDePrueba::html(), ArchivosDePrueba::svg(), ArchivosDePrueba::htmlRenombradoATxt()] as $archivo) {
            $this->subir($user, $archivo)
                ->assertUnprocessable()
                ->assertJsonPath('errors.archivo.0', TipoPermitidoPorContenido::MENSAJE);
        }

        $this->assertSame(0, Adjunto::query()->count());
    }

    public function test_rechaza_zip_aunque_se_llame_docx(): void
    {
        $this->subir($this->usuario(), ArchivosDePrueba::zipRenombradoADocx())->assertUnprocessable();
    }

    public function test_rechaza_archivo_de_mas_de_20_mb(): void
    {
        $this->subir($this->usuario(), ArchivosDePrueba::pdfDe(25))
            ->assertUnprocessable()
            ->assertJsonPath('errors.archivo.0', 'El archivo pesa 25 MB y el máximo es 20 MB.');

        $this->assertSame(0, Adjunto::query()->count());
        $this->assertSame([], Storage::disk('adjuntos')->allFiles());
    }

    public function test_rechaza_link_que_no_es_http_ni_https(): void
    {
        $user = $this->usuario();

        foreach (['ftp://drive.example/x', 'javascript:alert(1)', 'file:///etc/passwd', 'data:text/html,hola', 'drive.example/x', 'https://'] as $url) {
            $this->actingAs($user)
                ->postJson('/api/adjuntos', ['contexto' => self::CONTEXTO, 'url' => $url])
                ->assertUnprocessable()
                ->assertJsonPath('errors.url.0', LinkHttp::MENSAJE);
        }

        $this->actingAs($user)
            ->postJson('/api/adjuntos', ['contexto' => self::CONTEXTO, 'url' => 'https://drive.example/'.Str::repeat('a', 1000)])
            ->assertUnprocessable()
            ->assertJsonValidationErrorFor('url', 'errors');

        $this->assertSame(0, Adjunto::query()->count());
    }

    public function test_rechaza_contexto_inexistente(): void
    {
        $this->subir($this->usuario(), ArchivosDePrueba::pdf(), 'carpeta_drive')
            ->assertUnprocessable()
            ->assertJsonPath('errors.contexto.0', 'Ese lugar no admite adjuntos.');
    }

    public function test_rechaza_archivo_y_link_a_la_vez_o_ninguno(): void
    {
        $user = $this->usuario();

        $this->actingAs($user)
            ->postJson('/api/adjuntos', ['contexto' => self::CONTEXTO, 'archivo' => ArchivosDePrueba::pdf(), 'url' => 'https://drive.example/x'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.archivo.0', 'Subí un archivo o pegá un link, no los dos.');

        $this->actingAs($user)
            ->postJson('/api/adjuntos', ['contexto' => self::CONTEXTO])
            ->assertUnprocessable()
            ->assertJsonPath('errors.archivo.0', 'Subí un archivo o pegá un link.');
    }

    public function test_json_no_expone_la_ruta_en_disco(): void
    {
        $response = $this->subir($this->usuario(), ArchivosDePrueba::pdf())->assertCreated();
        $adjunto = $this->adjuntoDeLaRespuesta($response);

        $this->assertArrayNotHasKey('ruta', (array) $response->json('data'));
        $this->assertArrayNotHasKey('disco', (array) $response->json('data'));
        $this->assertStringNotContainsString((string) $adjunto->ruta, (string) $response->getContent());
        $this->assertArrayNotHasKey('ruta', $adjunto->toArray());
    }

    public function test_nombre_original_se_guarda_sin_rutas_ni_caracteres_de_control(): void
    {
        $archivo = UploadedFile::fake()->createWithContent("..\\..\\otra\"carpeta\x07.pdf", "%PDF-1.4\n%%EOF\n");

        $this->subir($this->usuario(), $archivo)->assertCreated()->assertJsonPath('data.nombre', 'otracarpeta.pdf');
    }

    public function test_subidas_limitadas_por_minuto_por_usuario(): void
    {
        config(['adjuntos.subidas_por_minuto' => 2]);
        $user = $this->usuario();

        $this->subir($user, ArchivosDePrueba::txt())->assertCreated();
        $this->subir($user, ArchivosDePrueba::txt())->assertCreated();
        $this->subir($user, ArchivosDePrueba::txt())->assertTooManyRequests()->assertJsonPath('data', null);

        $this->subir($this->usuario(), ArchivosDePrueba::txt())->assertCreated();
    }
}
