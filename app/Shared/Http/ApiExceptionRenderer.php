<?php

declare(strict_types=1);

namespace App\Shared\Http;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
        400 => 'La solicitud no es válida.',
        401 => 'No autenticado.',
        403 => 'No tenés permiso para realizar esta acción.',
        404 => 'Recurso no encontrado.',
        405 => 'Método no permitido.',
        409 => 'Conflicto con el estado actual del recurso.',
        419 => 'La sesión expiró. Volvé a ingresar.',
        422 => 'Los datos enviados no son válidos.',
        429 => 'Demasiadas solicitudes. Probá de nuevo en unos segundos.',
        500 => 'Error interno del servidor.',
        503 => 'Servicio no disponible momentáneamente.',
    ];

    public function __construct(private readonly bool $debug) {}

    public function render(Throwable $exception, Request $request): JsonResponse
    {
        return match (true) {
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
            $exception instanceof HttpExceptionInterface => $this->renderHttpException($exception),
            default => $this->renderServerError($exception),
        };
    }

    public static function messageFor(int $status): string
    {
        return self::STATUS_MESSAGES[$status]
            ?? ($status >= 500 ? self::STATUS_MESSAGES[500] : self::STATUS_MESSAGES[400]);
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
