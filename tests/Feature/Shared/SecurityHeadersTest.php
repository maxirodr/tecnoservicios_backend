<?php

declare(strict_types=1);

namespace Tests\Feature\Shared;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SecurityHeadersTest extends TestCase
{
    #[Test]
    public function root_returns_status_ok_without_exposing_the_stack(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertExactJson(['status' => 'ok'])
            ->assertHeaderMissing('X-Powered-By')
            ->assertHeaderMissing('Server');
    }

    #[Test]
    public function api_responses_do_not_expose_powered_by_or_server_headers(): void
    {
        $this->getJson('/api/no-existe')
            ->assertNotFound()
            ->assertHeaderMissing('X-Powered-By')
            ->assertHeaderMissing('Server');
    }

    /** SEG-07 */
    public function test_toda_respuesta_de_la_api_lleva_las_cabeceras_de_seguridad(): void
    {
        foreach ([$this->getJson('/api/health'), $this->getJson('/api/me'), $this->getJson('/api/no-existe')] as $response) {
            $response->assertHeader('X-Content-Type-Options', 'nosniff')
                ->assertHeader('X-Frame-Options', 'DENY')
                ->assertHeader('Referrer-Policy', 'no-referrer');
        }
    }

    /** SEG-07: en local la API corre por HTTP; HSTS se manda solo en producción. */
    public function test_hsts_solo_en_produccion(): void
    {
        $this->getJson('/api/health')->assertHeaderMissing('Strict-Transport-Security');

        $this->app->detectEnvironment(fn (): string => 'production');

        $this->getJson('/api/health')->assertHeader('Strict-Transport-Security', 'max-age=31536000');
    }

    #[Test]
    public function resend_webhook_route_is_not_exposed(): void
    {
        $this->postJson('/resend/webhook')->assertNotFound();
    }

    #[Test]
    public function welcome_view_is_gone(): void
    {
        $this->assertFileDoesNotExist(resource_path('views/welcome.blade.php'));
    }
}
