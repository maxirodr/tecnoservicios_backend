<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Support;

use Illuminate\Database\Connectors\MySqlConnector;
use Illuminate\Support\Facades\DB;
use PDO;
use Throwable;

/**
 * Chequeo de la base para /api/health (PU-139): un solo intento de conexión con timeout corto. Por
 * `DB::connection()->select()` Laravel reintenta al conectar y otra vez al reconectar: con MySQL
 * apagado eran cuatro esperas seguidas (~8 s).
 */
final class SondaDeBase extends MySqlConnector
{
    private const DRIVERS_MYSQL = ['mysql', 'mariadb'];

    public function responde(): bool
    {
        $nombre = (string) config('database.default');
        /** @var array<string, mixed> $config */
        $config = (array) config("database.connections.{$nombre}");

        try {
            if (! in_array($config['driver'] ?? null, self::DRIVERS_MYSQL, true)) {
                DB::connection($nombre)->select('select 1');

                return true;
            }

            $config['options'] = [PDO::ATTR_TIMEOUT => (int) config('salud.timeout_base_segundos')] + (array) ($config['options'] ?? []);
            $this->connect($config)->query('select 1');

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Sin reintento: si no conectó, la base no está para el health.
     *
     * @param  string  $dsn
     * @param  string  $username
     * @param  string  $password
     * @param  array<int, mixed>  $options
     */
    protected function tryAgainIfCausedByLostConnection(Throwable $e, $dsn, $username, #[\SensitiveParameter] $password, $options): never
    {
        throw $e;
    }
}
