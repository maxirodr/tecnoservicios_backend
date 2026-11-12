<?php

declare(strict_types=1);

namespace App\Shared\Http;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Envelope único de la API: { "data": ..., "message": string|null, "errors": object|null }.
 * Los listados paginados agregan "meta" y "links" dentro del mismo envelope.
 */
final class ApiResponse
{
    /**
     * @param  array<string, string>  $headers
     */
    public static function success(
        mixed $data = null,
        ?string $message = null,
        int $status = 200,
        array $headers = [],
    ): JsonResponse {
        if ($data instanceof ResourceCollection && $data->resource instanceof Paginator) {
            return self::paginated($data->resource, $data->resolve(), $message, $status, $headers);
        }

        if ($data instanceof Paginator) {
            return self::paginated($data, $data->items(), $message, $status, $headers);
        }

        if ($data instanceof JsonResource) {
            $data = $data->resolve();
        }

        return new JsonResponse(self::body($data, $message, null), $status, $headers);
    }

    public static function created(mixed $data = null, ?string $message = null): JsonResponse
    {
        return self::success($data, $message, 201);
    }

    /**
     * @param  array<string, mixed>|null  $errors
     * @param  array<string, string>  $headers
     */
    public static function error(
        string $message,
        int $status,
        ?array $errors = null,
        array $headers = [],
    ): JsonResponse {
        return new JsonResponse(self::body(null, $message, $errors), $status, $headers);
    }

    /**
     * @param  array<string, mixed>|null  $errors
     * @return array{data: mixed, message: string|null, errors: array<string, mixed>|null}
     */
    public static function body(mixed $data, ?string $message, ?array $errors): array
    {
        return [
            'data' => $data,
            'message' => $message,
            'errors' => $errors === [] ? null : $errors,
        ];
    }

    /**
     * @param  Paginator<array-key, mixed>  $paginator
     * @param  array<array-key, mixed>  $items
     * @param  array<string, string>  $headers
     */
    private static function paginated(
        Paginator $paginator,
        array $items,
        ?string $message,
        int $status,
        array $headers,
    ): JsonResponse {
        $meta = [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];

        $links = [
            'prev' => $paginator->previousPageUrl(),
            'next' => $paginator->nextPageUrl(),
        ];

        if ($paginator instanceof LengthAwarePaginator) {
            $meta['total'] = $paginator->total();
            $meta['last_page'] = $paginator->lastPage();
            $links['first'] = $paginator->url(1);
            $links['last'] = $paginator->url($paginator->lastPage());
        }

        $body = self::body(array_values($items), $message, null);
        $body['meta'] = $meta;
        $body['links'] = $links;

        return new JsonResponse($body, $status, $headers);
    }
}
