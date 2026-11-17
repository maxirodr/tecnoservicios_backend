<?php

declare(strict_types=1);

namespace App\Shared\Http;

use Illuminate\Database\LostConnectionDetector;
use Illuminate\Database\LostConnectionException;
use PDOException;
use Predis\CommunicationException;
use Predis\Connection\Resource\Exception\StreamInitException;
use Throwable;

/**
 * PU-138: distingue "no hay conexión con la base o con Redis" (503, el sistema no está disponible) de un
 * error propio (500). Solo fallas de conexión: una QueryException por un SQL mal armado o una clave
 * duplicada sigue siendo 500.
 */
final class ServicioCaido
{
    /** Códigos del cliente MySQL: no se pudo conectar (2002, 2003), se fue el servidor (2006, 2013). */
    private const CODIGOS_MYSQL = [2002, 2003, 2006, 2013];

    /** Clases de phpredis: pueden no existir si la extensión no está (en Windows se usa predis). */
    private const CLASES_PHPREDIS = ['RedisException', 'RedisClusterException'];

    public static function es(Throwable $e): bool
    {
        for ($actual = $e; $actual !== null; $actual = $actual->getPrevious()) {
            if (self::esDeConexion($actual)) {
                return true;
            }
        }

        return false;
    }

    private static function esDeConexion(Throwable $e): bool
    {
        if ($e instanceof LostConnectionException
            || $e instanceof StreamInitException
            || $e instanceof CommunicationException) {
            return true;
        }

        foreach (self::CLASES_PHPREDIS as $clase) {
            if (is_a($e, $clase)) {
                return true;
            }
        }

        if (! $e instanceof PDOException) {
            return false;
        }

        $codigo = $e->errorInfo[1] ?? $e->getCode();
        $sqlState = (string) ($e->errorInfo[0] ?? $e->getCode());

        return in_array((int) $codigo, self::CODIGOS_MYSQL, true)
            || str_starts_with($sqlState, '08')
            || (new LostConnectionDetector)->causedByLostConnection($e);
    }
}
