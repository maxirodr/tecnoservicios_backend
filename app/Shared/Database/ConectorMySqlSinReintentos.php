<?php

declare(strict_types=1);

namespace App\Shared\Database;

use App\Shared\Http\ServicioCaido;
use Exception;
use Illuminate\Database\Connectors\MySqlConnector;
use PDO;

/**
 * PU-202: conector de MySQL que intenta conectar **una sola vez por pedido**.
 *
 * El de Laravel reintenta si la conexión falla, y la conexión vuelve a conectar al repetir la consulta:
 * con MySQL caído son 4 esperas de `DB_CONNECT_TIMEOUT` por consulta, y cada consulta más del pedido
 * suma otras 4 (con 2 s eran ~8 s por consulta y más de 30 s por pedido). Acá, si no se pudo conectar
 * (falla de conexión según `ServicioCaido`), se recuerda en `BasesCaidas` y el resto del pedido falla al
 * instante: el 503 sale en ~`DB_CONNECT_TIMEOUT`. Cualquier otro error de conexión (usuario o clave
 * mal, base inexistente) se lanza igual que antes, sin recordarlo.
 */
final class ConectorMySqlSinReintentos extends MySqlConnector
{
    public function __construct(private readonly BasesCaidas $caidas) {}

    /**
     * @param  string  $dsn
     * @param  array<string, mixed>  $config
     * @param  array<int, mixed>  $options
     *
     * @throws Exception
     */
    public function createConnection($dsn, array $config, array $options): PDO
    {
        $falla = $this->caidas->fallaDe($dsn);
        if ($falla !== null) {
            throw $falla;
        }

        $username = $config['username'] ?? '';
        $password = $config['password'] ?? '';

        try {
            return $this->createPdoConnection(
                $dsn,
                is_string($username) ? $username : '',
                is_string($password) ? $password : '',
                $options,
            );
        } catch (Exception $e) {
            if (ServicioCaido::es($e)) {
                $this->caidas->registrar($dsn, $e);
            }

            throw $e;
        }
    }
}
