<?php

declare(strict_types=1);

namespace App\Shared\Http;

use Illuminate\Http\JsonResponse;

/**
 * Atajo para controllers: devuelven siempre el envelope { data, message, errors }.
 */
trait RespondsWithEnvelope
{
    protected function ok(mixed $data = null, ?string $message = null, int $status = 200): JsonResponse
    {
        return ApiResponse::success($data, $message, $status);
    }

    protected function created(mixed $data = null, ?string $message = null): JsonResponse
    {
        return ApiResponse::created($data, $message);
    }

    protected function noContent(?string $message = null): JsonResponse
    {
        return ApiResponse::success(null, $message);
    }

    /**
     * @param  array<string, mixed>|null  $errors
     */
    protected function fail(string $message, int $status, ?array $errors = null): JsonResponse
    {
        return ApiResponse::error($message, $status, $errors);
    }
}
