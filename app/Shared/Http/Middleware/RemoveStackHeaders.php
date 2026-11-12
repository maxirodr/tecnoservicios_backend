<?php

declare(strict_types=1);

namespace App\Shared\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * No exponer nombre ni versión del stack: quita X-Powered-By y Server de toda respuesta.
 * `header_remove` cubre lo que agrega PHP por fuera del objeto Response.
 */
final class RemoveStackHeaders
{
    private const LEAKY_HEADERS = ['X-Powered-By', 'Server'];

    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        foreach (self::LEAKY_HEADERS as $header) {
            $response->headers->remove($header);

            if (! headers_sent()) {
                header_remove($header);
            }
        }

        return $response;
    }
}
