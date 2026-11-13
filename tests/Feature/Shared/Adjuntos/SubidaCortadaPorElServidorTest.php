<?php

declare(strict_types=1);

namespace Tests\Feature\Shared\Adjuntos;

use App\Shared\Adjuntos\Adjunto;
use App\Shared\Adjuntos\LimiteDeSubida;
use App\Shared\Http\ApiExceptionRenderer;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Tests\Feature\Shared\Adjuntos\Support\AdjuntosTestCase;

/**
 * B-04 (QC entrega 1) · HU-025 criterio 3: si PHP corta la subida antes que la validación
 * (`upload_max_filesize` o `post_max_size`), la persona lee el tamaño en MB y el máximo real,
 * con 413, y no un mensaje genérico.
 */
final class SubidaCortadaPorElServidorTest extends AdjuntosTestCase
{
    private const MB = 1048576;

    private const MULTIPART = 'multipart/form-data; boundary=----limite';

    public function test_archivo_cortado_por_upload_max_filesize_responde_413_con_el_maximo_en_mb(): void
    {
        $cortado = new UploadedFile(__FILE__, 'certificado-escaneado.pdf', 'application/pdf', UPLOAD_ERR_INI_SIZE, true);

        $this->actingAs($this->usuario())
            ->call('POST', '/api/adjuntos', ['contexto' => self::CONTEXTO], [], ['archivo' => $cortado], [
                'HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => self::MULTIPART,
            ])
            ->assertStatus(413)
            ->assertJsonPath('data', null)
            ->assertJsonPath('message', LimiteDeSubida::delServidor()->mensaje());

        $this->assertMatchesRegularExpression('/^El archivo pesa más de \d+ MB, que es el máximo\.$/', LimiteDeSubida::delServidor()->mensaje());
        $this->assertSame(0, Adjunto::query()->count());
    }

    public function test_multipart_que_llega_vacio_por_post_max_size_responde_413(): void
    {
        $this->actingAs($this->usuario())
            ->call('POST', '/api/adjuntos', [], [], [], [
                'HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => self::MULTIPART,
            ])
            ->assertStatus(413)
            ->assertJsonPath('message', LimiteDeSubida::delServidor()->mensaje());
    }

    public function test_post_mas_grande_que_post_max_size_responde_413_con_el_peso_en_mb(): void
    {
        $request = Request::create('/api/adjuntos', 'POST', server: ['CONTENT_LENGTH' => (string) (25 * self::MB)]);

        $respuesta = (new ApiExceptionRenderer(false))->render(new PostTooLargeException, $request);

        $this->assertSame(413, $respuesta->getStatusCode());
        $this->assertSame(
            ['data' => null, 'message' => LimiteDeSubida::delServidor()->mensaje(25 * self::MB), 'errors' => null],
            $respuesta->getData(true),
        );
        $this->assertMatchesRegularExpression('/^El archivo pesa 25 MB y el máximo es \d+ MB\.$/', LimiteDeSubida::delServidor()->mensaje(25 * self::MB));
    }

    public function test_subida_de_25_mb_que_php_no_acepta_llega_al_usuario_con_el_tamano(): void
    {
        $respuesta = $this->actingAs($this->usuario())
            ->call('POST', '/api/adjuntos', [], [], [], [
                'HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => self::MULTIPART,
                'CONTENT_LENGTH' => (string) (25 * self::MB),
            ])
            ->assertStatus(413);

        $this->assertStringStartsWith('El archivo pesa ', (string) $respuesta->json('message'));
    }

    public function test_un_pedido_json_sin_datos_sigue_siendo_un_422(): void
    {
        $this->actingAs($this->usuario())
            ->postJson('/api/adjuntos', [])
            ->assertUnprocessable()
            ->assertJsonPath('errors.archivo.0', 'Subí un archivo o pegá un link.');
    }
}
