<?php

declare(strict_types=1);

namespace Tests\Feature\Shared;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SpaAuthConfigurationTest extends TestCase
{
    #[Test]
    public function cors_preflight_from_the_spa_allows_credentials(): void
    {
        $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Access-Control-Request-Method' => 'POST',
        ])->options('/api/health')
            ->assertNoContent()
            ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5173')
            ->assertHeader('Access-Control-Allow-Credentials', 'true');
    }

    #[Test]
    public function cors_never_allows_unknown_origins(): void
    {
        $response = $this->withHeaders([
            'Origin' => 'https://evil.example.com',
            'Access-Control-Request-Method' => 'POST',
        ])->options('/api/health');

        // Con un solo origen permitido, el header siempre vale ese origen: el navegador bloquea al resto.
        $this->assertNotSame('https://evil.example.com', $response->headers->get('Access-Control-Allow-Origin'));
        $this->assertNotSame('*', $response->headers->get('Access-Control-Allow-Origin'));
    }

    #[Test]
    public function csrf_cookie_endpoint_issues_xsrf_token_for_the_spa(): void
    {
        $this->get('/sanctum/csrf-cookie')
            ->assertNoContent()
            ->assertCookie('XSRF-TOKEN');
    }

    #[Test]
    public function spa_origin_is_treated_as_stateful(): void
    {
        $stateful = config('sanctum.stateful');

        $this->assertIsArray($stateful);
        $this->assertContains('localhost:5173', $stateful);
    }

    #[Test]
    public function session_cache_and_queue_are_configured_on_redis_outside_tests(): void
    {
        $example = (string) file_get_contents(base_path('.env.example'));

        $this->assertStringContainsString("SESSION_DRIVER=redis\n", $example);
        $this->assertStringContainsString("CACHE_STORE=redis\n", $example);
        $this->assertStringContainsString("QUEUE_CONNECTION=redis\n", $example);
        $this->assertStringContainsString("MAIL_MAILER=resend\n", $example);
    }
}
