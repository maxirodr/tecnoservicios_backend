<?php

declare(strict_types=1);

namespace Tests\Unit\Shared;

use App\Shared\Http\ApiExceptionRenderer;
use App\Shared\Http\ApiResponse;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ApiResponseTest extends TestCase
{
    #[Test]
    public function envelope_always_has_data_message_and_errors_keys(): void
    {
        $this->assertSame(['data' => null, 'message' => null, 'errors' => null], ApiResponse::body(null, null, null));
    }

    #[Test]
    public function empty_error_bag_is_normalized_to_null(): void
    {
        $this->assertNull(ApiResponse::body(null, 'x', [])['errors']);
    }

    #[Test]
    public function unknown_server_statuses_fall_back_to_generic_500_message(): void
    {
        $this->assertSame('El sistema tuvo un problema. Volvé a intentar en un momento.', ApiExceptionRenderer::messageFor(502));
        $this->assertSame('No pudimos procesar lo que enviaste. Revisá los datos y volvé a intentar.', ApiExceptionRenderer::messageFor(418));
    }
}
