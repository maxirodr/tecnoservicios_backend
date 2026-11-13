<?php

declare(strict_types=1);

namespace Tests\Feature\Shared;

use App\Shared\Http\ApiExceptionRenderer;
use Tests\Feature\Identidad\IdentidadTestCase;

/** SEG-03: límite general de la API, por usuario con sesión y por IP sin sesión (120/min por defecto). */
final class LimiteGeneralApiTest extends IdentidadTestCase
{
    private const LIMITE = 3;

    private const TOO_MANY_REQUESTS = 429;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.api_requests_per_minute' => self::LIMITE]);
    }

    public function test_el_limite_por_defecto_es_120_pedidos_por_minuto(): void
    {
        /** @var array<string, mixed> $config */
        $config = require config_path('app.php');

        $this->assertSame(120, $config['api_requests_per_minute']);
    }

    public function test_pasado_el_limite_por_minuto_el_usuario_recibe_429_en_el_envelope(): void
    {
        $this->actingAs($this->usuarioDe('Compras'));

        for ($i = 0; $i < self::LIMITE; $i++) {
            $this->getJson('/api/me')->assertOk();
        }

        $this->getJson('/api/me')
            ->assertStatus(self::TOO_MANY_REQUESTS)
            ->assertHeader('Retry-After')
            ->assertExactJson([
                'data' => null,
                'message' => ApiExceptionRenderer::messageFor(self::TOO_MANY_REQUESTS),
                'errors' => null,
            ]);
    }

    public function test_el_limite_se_cuenta_por_usuario(): void
    {
        $this->actingAs($this->usuarioDe('Compras'));
        for ($i = 0; $i < self::LIMITE; $i++) {
            $this->getJson('/api/me')->assertOk();
        }

        $this->actingAs($this->usuarioDe('Compras'));
        $this->getJson('/api/me')->assertOk();
    }

    public function test_sin_sesion_el_limite_se_cuenta_por_ip(): void
    {
        for ($i = 0; $i < self::LIMITE; $i++) {
            $this->getJson('/api/health')->assertOk();
        }

        $this->getJson('/api/health')->assertStatus(self::TOO_MANY_REQUESTS)->assertJsonPath('data', null);
    }
}
