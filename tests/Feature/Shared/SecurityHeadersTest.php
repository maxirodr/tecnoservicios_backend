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
