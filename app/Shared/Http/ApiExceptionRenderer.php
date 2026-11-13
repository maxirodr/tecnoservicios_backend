<?php

declare(strict_types=1);

namespace App\Shared\Http;

use App\Shared\Adjuntos\LimiteDeSubida;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Convierte cualquier excepción en el envelope { data, message, errors }.
 * En producción nunca expone mensajes internos, clases ni trazas de un 500.
 */
final class ApiExceptionRenderer
{
    private const STATUS_MESSAGES = [
        400 => 'No pudimos procesar lo que enviaste. Revisá los datos y volvé a intentar.',
        401 => 'Tenés que ingresar para seguir.',
        403 => 'No tenés permiso para hacer esto.',
        404 => 'No encontramos lo que buscabas.',
        405 => 'Esa acción no está disponible.',
        409 => 'Alguien cambió esto mientras lo tenías abierto. Recargá y volvé a intentar.',
        413 => 'Lo que enviaste es demasiado grande. Si es un archivo, probá con uno más liviano.',
        419 => 'La sesión expiró. Volvé a ingresar.',
        422 => 'Los datos enviados no son válidos.',
        429 => 'Fueron demasiadas acciones seguidas. Esperá unos segundos y volvé a intentar.',
        500 => 'El sistema tuvo un problema. Volvé a intentar en un momento.',
        503 => 'El sistema no está disponible en este momento. Probá de nuevo en unos minutos.',
    ];

    public function __construct(private readonly bool $debug) {}

    public function render(Throwable $exception, Request $request): JsonResponse
    {
        return match (true) {
            // Respuesta ya armada con el envelope (p. ej. el `response()` de un rate limiter con mensaje propio).
            $exception instanceof HttpResponseException && $exception->getResponse() instanceof JsonResponse => $exception->getResponse(),
            $exception instanceof ValidationException => ApiResponse::error(
                self::messageFor(422),
                $exception->status,
                $exception->errors(),
            ),
            $exception instanceof AuthenticationException => ApiResponse::error(self::messageFor(401), 401),
            $exception instanceof AuthorizationException => ApiResponse::error(
                self::messageFor($exception->status() ?? 403),
                $exception->status() ?? 403,
            ),
            $exception instanceof ModelNotFoundException => ApiResponse::error(self::messageFor(404), 404),
            $exception instanceof TokenMismatchException => ApiResponse::error(self::messageFor(419), 419),
            // PHP descartó el cuerpo por `post_max_size`: el único envío grande es un archivo (HU-025 c. 3).
            $exception instanceof PostTooLargeException => ApiResponse::error(
                LimiteDeSubida::delServidor()->mensaje(self::largoDelCuerpo($request)),
                413,
            ),
            $exception instanceof HttpExceptionInterface => $this->renderHttpException($exception),
            default => $this->renderServerError($exception),
        };
    }

    public static function messageFor(int $status): string
    {
        return self::STATUS_MESSAGES[$status]
            ?? ($status >= 500 ? self::STATUS_MESSAGES[500] : self::STATUS_MESSAGES[400]);
    }

    private static function largoDelCuerpo(Request $request): ?int
    {
        $largo = $request->server('CONTENT_LENGTH');

        return is_numeric($largo) ? (int) $largo : null;
    }

    private function renderHttpException(HttpExceptionInterface $exception): JsonResponse
    {
        $status = $exception->getStatusCode();

        /** @var array<string, string> $headers */
        $headers = $exception->getHeaders();

        return ApiResponse::error(self::messageFor($status), $status, null, $headers);
    }

    private function renderServerError(Throwable $exception): JsonResponse
    {
        $errors = $this->debug
            ? ['debug' => [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
                'file' => $exception->getFile().':'.$exception->getLine(),
            ]]
            : null;

        return ApiResponse::error(self::messageFor(500), 500, $errors);
    }
}
