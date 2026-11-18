<?php

declare(strict_types=1);

namespace App\Shared\Database;

use Throwable;

/**
 * PU-202: recuerda, durante un pedido (o un job), a qué base no se pudo conectar. Con MySQL caído,
 * Laravel reintenta la conexión (al conectar y otra vez al repetir la consulta) y cada intento espera
 * `DB_CONNECT_TIMEOUT`; además, varias partes del pedido vuelven a consultar (límite por usuario, sesión,
 * log del error). Después de la primera falla, los intentos siguientes fallan al instante con la misma
 * excepción. Se registra `scoped`: se olvida al terminar el pedido o el job.
 */
final class BasesCaidas
{
    /** @var array<string, Throwable> falla por DSN (host, puerto y base) */
    private array $fallas = [];

    public function registrar(string $dsn, Throwable $falla): void
    {
        $this->fallas[$dsn] = $falla;
    }

    public function fallaDe(string $dsn): ?Throwable
    {
        return $this->fallas[$dsn] ?? null;
    }
}
