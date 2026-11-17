<?php

declare(strict_types=1);

namespace App\Shared\Concurrencia;

use App\Shared\Http\ApiResponse;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Exceptions\HttpResponseException;
use Throwable;

/**
 * Bloqueo optimista por `updated_at` (PU-002): el formulario manda el `updated_at` que cargó; si la fila
 * cambió desde entonces (a nivel de segundos), 409 y no se guarda nada. Sin `updated_at`, como siempre.
 */
final class VersionDeFila
{
    public const CAMPO = 'updated_at';

    public const MENSAJE = 'Alguien cambió esto mientras lo tenías abierto. Actualizá y revisá.';

    /**
     * @return array<string, list<string>>
     */
    public static function reglas(): array
    {
        return [self::CAMPO => ['sometimes', 'nullable', 'date']];
    }

    public static function desde(mixed $valor): ?CarbonImmutable
    {
        if (! is_string($valor) || trim($valor) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($valor);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Dentro de la transacción: bloquea la fila (`lockForUpdate`), verifica la versión y deja el modelo
     * con lo que hay hoy en la base, para que el historial compare contra lo último guardado.
     *
     * @throws HttpResponseException 409 si la versión no coincide
     */
    public static function bloquearYVerificar(Model $modelo, ?CarbonInterface $esperada): void
    {
        $actual = $modelo->newQuery()->whereKey($modelo->getKey())->lockForUpdate()->firstOrFail();
        $guardada = $actual->getAttribute(self::CAMPO);

        if ($esperada !== null
            && (! $guardada instanceof CarbonInterface || $guardada->getTimestamp() !== $esperada->getTimestamp())) {
            throw new HttpResponseException(ApiResponse::error(self::MENSAJE, 409, [self::CAMPO => [self::MENSAJE]]));
        }

        $modelo->setRawAttributes($actual->getAttributes(), true);
    }

    public static function iso(?CarbonInterface $fecha): ?string
    {
        return $fecha?->toIso8601String();
    }
}
