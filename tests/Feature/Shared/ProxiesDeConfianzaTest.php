<?php

declare(strict_types=1);

namespace Tests\Feature\Shared;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/** SEG-04: X-Forwarded-For solo vale si viene de un proxy listado en TRUSTED_PROXIES. */
final class ProxiesDeConfianzaTest extends TestCase
{
    private const IP_FALSA = '203.0.113.9';

    protected function setUp(): void
    {
        parent::setUp();

        Route::get('api/_proxies/ip', fn (Request $request): array => ['ip' => $request->ip()]);
    }

    protected function tearDown(): void
    {
        putenv('TRUSTED_PROXIES');

        parent::tearDown();
    }

    public function test_sin_proxies_de_confianza_se_ignora_x_forwarded_for(): void
    {
        config(['trustedproxy.proxies' => []]);

        $this->ipVista()->assertExactJson(['ip' => '127.0.0.1']);
    }

    public function test_desde_un_proxy_de_confianza_vale_la_ip_que_informa(): void
    {
        config(['trustedproxy.proxies' => ['127.0.0.1']]);

        $this->ipVista()->assertExactJson(['ip' => self::IP_FALSA]);
    }

    public function test_desde_un_proxy_que_no_esta_en_la_lista_se_ignora_x_forwarded_for(): void
    {
        config(['trustedproxy.proxies' => ['10.0.0.5']]);

        $this->ipVista()->assertExactJson(['ip' => '127.0.0.1']);
    }

    public function test_trusted_proxies_se_lee_separado_por_comas_y_vacio_es_ninguno(): void
    {
        putenv('TRUSTED_PROXIES= 127.0.0.1, 10.0.0.0/8 ,');
        $this->assertSame(['127.0.0.1', '10.0.0.0/8'], $this->configLeida());

        putenv('TRUSTED_PROXIES=');
        $this->assertSame([], $this->configLeida());
    }

    /**
     * @return TestResponse<Response>
     */
    private function ipVista(): TestResponse
    {
        return $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->withHeader('X-Forwarded-For', self::IP_FALSA)
            ->get('/api/_proxies/ip')
            ->assertOk();
    }

    /**
     * @return list<string>
     */
    private function configLeida(): array
    {
        /** @var array{proxies: list<string>} $config */
        $config = require config_path('trustedproxy.php');

        return $config['proxies'];
    }
}
