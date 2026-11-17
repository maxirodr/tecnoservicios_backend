<?php

declare(strict_types=1);

namespace Tests\Feature\Shared\Adjuntos;

use App\Shared\Adjuntos\Adjunto;
use App\Shared\Adjuntos\Http\Rules\LinkHttp;
use App\Shared\Adjuntos\Http\Rules\TipoPermitidoPorContenido;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;
use Tests\Feature\Shared\Adjuntos\Support\AdjuntosTestCase;

/** PU-010, PU-011, PU-026, PU-030: links con tildes y mensajes que dicen el motivo real del rechazo. */
final class MotivoDelRechazoTest extends AdjuntosTestCase
{
    public function test_link_con_enie_y_tildes_se_acepta_y_se_guarda_como_se_escribio(): void
    {
        $this->pegar('https://drive.example/oferta-ñandú?v=técnica')
            ->assertCreated()
            ->assertJsonPath('data.url', 'https://drive.example/oferta-ñandú?v=técnica')
            ->assertJsonPath('data.nombre', 'drive.example');

        $this->pegar('https://drive.example/oferta-%C3%B1and%C3%BA')
            ->assertCreated()
            ->assertJsonPath('data.url', 'https://drive.example/oferta-%C3%B1and%C3%BA');
    }

    public function test_dominio_con_enie_se_acepta_y_se_muestra_legible(): void
    {
        $this->pegar('https://ñandú.example/carpeta')
            ->assertCreated()
            ->assertJsonPath('data.url', 'https://ñandú.example/carpeta')
            ->assertJsonPath('data.nombre', 'ñandú.example');
    }

    public function test_link_con_espacio_dice_que_no_puede_tener_espacios(): void
    {
        $this->pegar('https://exa mple.com/x')
            ->assertUnprocessable()
            ->assertJsonPath('errors.url.0', LinkHttp::CON_ESPACIOS);
    }

    public function test_dominio_sin_punto_no_parece_un_link(): void
    {
        foreach (['https://hola', 'http://localhost/x', 'https://', 'https://.'] as $url) {
            $this->pegar($url)
                ->assertUnprocessable()
                ->assertJsonPath('errors.url.0', LinkHttp::NO_PARECE_LINK);
        }

        $this->assertSame(0, Adjunto::query()->count());
    }

    public function test_sin_http_sigue_pidiendo_que_empiece_con_http(): void
    {
        $this->pegar('drive.example/carpeta')
            ->assertUnprocessable()
            ->assertJsonPath('errors.url.0', LinkHttp::MENSAJE);
    }

    public function test_archivo_vacio_dice_que_esta_vacio(): void
    {
        $this->subir($this->usuario(), UploadedFile::fake()->createWithContent('vacio.pdf', ''))
            ->assertUnprocessable()
            ->assertJsonPath('errors.archivo.0', TipoPermitidoPorContenido::VACIO);
    }

    public function test_tipo_no_aceptado_usa_el_mismo_texto_que_el_front(): void
    {
        $this->subir($this->usuario(), UploadedFile::fake()->createWithContent('programa.exe', "MZ\x90\x00".str_repeat("\x00", 64)))
            ->assertUnprocessable()
            ->assertJsonPath('errors.archivo.0', 'Ese tipo de archivo no se puede subir. Se aceptan PDF, imágenes (JPG, PNG, WEBP, HEIC), Excel, Word, CSV y TXT.');
    }

    public function test_extension_que_miente_dice_que_tipo_no_es(): void
    {
        $this->subir($this->usuario(), UploadedFile::fake()->createWithContent('planilla.XLSX', "MZ\x90\x00".str_repeat("\x00", 64)))
            ->assertUnprocessable()
            ->assertJsonPath('errors.archivo.0', 'Este archivo no es un Excel aunque termine en .xlsx. Elegí otro.');
    }

    /**
     * @return TestResponse<JsonResponse>
     */
    private function pegar(string $url): TestResponse
    {
        return $this->actingAs($this->usuario())
            ->postJson('/api/adjuntos', ['contexto' => self::CONTEXTO, 'url' => $url]);
    }
}
