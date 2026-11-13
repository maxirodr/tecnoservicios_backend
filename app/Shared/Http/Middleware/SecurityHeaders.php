<?php

declare(strict_types=1);

namespace App\Shared\Http\Middleware;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeceras de seguridad en toda respuesta de la API (SEG-07). HSTS solo en producción: en local la
 * API corre por HTTP y el navegador recordaría la regla para localhost.
 */
final class SecurityHeaders
{
    private const HEADERS = [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'DENY',
        'Referrer-Policy' => 'no-referrer',
    ];

    private const HSTS = 'max-age=31536000';

    public function __construct(private readonly Application $app) {}

    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        foreach (self::HEADERS as $header => $value) {
            $response->headers->set($header, $value);
        }

        if ($this->app->environment('production')) {
            $response->headers->set('Strict-Transport-Security', self::HSTS);
        }

        return $response;
    }
}
