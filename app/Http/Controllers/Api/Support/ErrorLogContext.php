<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Support;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Arr;
use Throwable;

/**
 * Contexto común de lo que va al canal `errors` (y sale por GET /api/errors).
 *
 * Nunca se registran valores del input (SEG-02): ni mails, teléfonos, CUIT, importes ni el código de
 * ingreso. Del input van solo las claves; de una validación fallida, solo los campos; la URL va sin
 * query string (sus valores también son input).
 */
final class ErrorLogContext
{
    private const TRACE_FRAMES = 5;

    /**
     * @return array<string, mixed>
     */
    public static function fromRequest(Request $request): array
    {
        $route = $request->route();

        return [
            'action' => $route instanceof Route ? $route->getActionName() : 'unknown',
            'method' => $request->method(),
            'url' => $request->url(),
            'user_id' => $request->user()?->getAuthIdentifier(),
            'ip' => $request->ip(),
            'input' => self::inputKeys($request->all()),
        ];
    }

    /**
     * Claves del input en notación de puntos (`contactos.0.email`), sin valores.
     *
     * @param  array<mixed>  $input
     * @return list<string>
     */
    public static function inputKeys(array $input): array
    {
        return array_map(strval(...), array_keys(Arr::dot($input)));
    }

    /**
     * Campos con error, sin los mensajes (un mensaje puede repetir un dato cargado).
     *
     * @param  array<mixed>  $errors
     * @return list<string>
     */
    public static function validationFields(array $errors): array
    {
        return array_map(strval(...), array_keys($errors));
    }

    /**
     * @return list<string>
     */
    public static function shortTrace(Throwable $e): array
    {
        return array_map(
            static fn (array $frame): string => ($frame['class'] ?? '')
                .($frame['type'] ?? '')
                .$frame['function']
                .' ('.basename($frame['file'] ?? '').':'.($frame['line'] ?? '?').')',
            array_slice($e->getTrace(), 0, self::TRACE_FRAMES),
        );
    }
}
