<?php

declare(strict_types=1);

namespace App\Shared\Idempotencia;

use App\Shared\Http\ApiResponse;
use Closure;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware `idempotent` (ADR-011). Con `Idempotency-Key` (UUID) el doble envío devuelve la misma
 * respuesta sin re-ejecutar. La clave es por usuario; reusarla con otra ruta u otro payload → 422;
 * si el primer envío todavía se está procesando → 409. Solo se guardan las respuestas 2xx: un error
 * libera la clave para que el reintento se procese de nuevo. Sin cabecera, el request pasa igual.
 */
final readonly class Idempotent
{
    public const HEADER = 'Idempotency-Key';

    public const REPLAYED_HEADER = 'Idempotent-Replayed';

    /** Un `en_curso` más viejo que esto quedó abandonado (el proceso murió): se puede retomar. */
    private const SEGUNDOS_EN_CURSO = 60;

    private const SEGUNDOS_REINTENTO = 1;

    private const INTENTOS_DE_RESERVA = 3;

    public function __construct(private RequestFingerprint $fingerprint) {}

    public function handle(Request $request, Closure $next): Response
    {
        $clave = $request->headers->get(self::HEADER);
        $userId = $request->user()?->getAuthIdentifier();

        if ($clave === null || $clave === '' || ! is_numeric($userId)) {
            return $next($request);
        }

        if (! Str::isUuid($clave)) {
            return ApiResponse::error('Los datos enviados no son válidos.', 422, [
                'idempotency_key' => ['La Idempotency-Key tiene que ser un UUID.'],
            ]);
        }

        $ruta = $request->method().' '.$request->path();
        $reserva = $this->reservar((int) $userId, strtolower($clave), $ruta, $this->fingerprint->hash($request));

        if ($reserva instanceof Response) {
            return $reserva;
        }

        $guardada = false;

        try {
            $response = $next($request);
            $guardada = $this->guardar($reserva, $response);

            return $response;
        } finally {
            if (! $guardada) {
                $reserva->delete();
            }
        }
    }

    private function reservar(int $userId, string $clave, string $ruta, string $hash): IdempotencyKey|Response
    {
        for ($intento = 1; $intento <= self::INTENTOS_DE_RESERVA; $intento++) {
            try {
                return IdempotencyKey::query()->create([
                    'user_id' => $userId,
                    'clave' => $clave,
                    'ruta' => $ruta,
                    'payload_hash' => $hash,
                    'status' => IdempotencyKey::EN_CURSO,
                ]);
            } catch (UniqueConstraintViolationException) {
                $existente = IdempotencyKey::query()->where('user_id', $userId)->where('clave', $clave)->first();

                if ($existente === null || $this->liberarSiCaducada($existente)) {
                    continue;
                }

                return $this->responderA($existente, $ruta, $hash);
            }
        }

        return $this->enCurso();
    }

    /** Borra la clave si venció o quedó abandonada; true si la liberó (y se puede reservar de nuevo). */
    private function liberarSiCaducada(IdempotencyKey $existente): bool
    {
        $abandonada = $existente->status === IdempotencyKey::EN_CURSO
            && $existente->updated_at->lte(Carbon::now()->subSeconds(self::SEGUNDOS_EN_CURSO));

        if (! $existente->vencida() && ! $abandonada) {
            return false;
        }

        IdempotencyKey::query()
            ->whereKey($existente->getKey())
            ->where('updated_at', $existente->updated_at)
            ->delete();

        return true;
    }

    private function responderA(IdempotencyKey $existente, string $ruta, string $hash): Response
    {
        if (! $existente->coincideCon($ruta, $hash)) {
            return ApiResponse::error('Los datos enviados no son válidos.', 422, [
                'idempotency_key' => ['Esta Idempotency-Key ya se usó con otros datos.'],
            ]);
        }

        if ($existente->status === IdempotencyKey::EN_CURSO || $existente->respuesta === null) {
            return $this->enCurso();
        }

        $respuesta = $existente->respuesta;

        return new Response($respuesta['body'], $respuesta['status'], array_filter([
            'Content-Type' => $respuesta['content_type'],
            self::REPLAYED_HEADER => 'true',
        ]));
    }

    private function enCurso(): Response
    {
        return ApiResponse::error(
            'Esta operación todavía se está procesando. Probá de nuevo en unos segundos.',
            409,
            null,
            ['Retry-After' => (string) self::SEGUNDOS_REINTENTO],
        );
    }

    private function guardar(IdempotencyKey $reserva, Response $response): bool
    {
        $body = $response->getContent();

        if (! $response->isSuccessful() || $body === false) {
            return false;
        }

        $reserva->forceFill([
            'status' => IdempotencyKey::COMPLETADA,
            'respuesta' => [
                'status' => $response->getStatusCode(),
                'content_type' => $response->headers->get('Content-Type'),
                'body' => $body,
            ],
        ])->save();

        return true;
    }
}
