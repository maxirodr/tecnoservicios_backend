<?php

declare(strict_types=1);

namespace App\Shared\Http;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use InvalidArgumentException;

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
        if (($data instanceof ResourceCollection && $data->resource instanceof Paginator) || $data instanceof Paginator) {
            return self::paginated($data, null, [], $message, $status, $headers);
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
     * Listado paginado, con datos extra en `meta` (contadores, totales visibles…) sin desarmar el sobre en
     * el controller (TL-03). `$items` reemplaza las filas de la página cuando el controller ya las armó.
     *
     * @param  Paginator<array-key, mixed>|ResourceCollection  $datos
     * @param  array<array-key, mixed>|null  $items
     * @param  array<string, mixed>  $metaExtra
     * @param  array<string, string>  $headers
     */
    public static function paginated(
        Paginator|ResourceCollection $datos,
        ?array $items = null,
        array $metaExtra = [],
        ?string $message = null,
        int $status = 200,
        array $headers = [],
    ): JsonResponse {
        $paginator = $datos instanceof ResourceCollection ? $datos->resource : $datos;

        if (! $paginator instanceof Paginator) {
            throw new InvalidArgumentException('ApiResponse::paginated necesita un paginador.');
        }

        $items ??= $datos instanceof ResourceCollection ? $datos->resolve() : $paginator->items();

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
        $body['meta'] = [...$meta, ...$metaExtra];
        $body['links'] = $links;

        return new JsonResponse($body, $status, $headers);
    }
}
