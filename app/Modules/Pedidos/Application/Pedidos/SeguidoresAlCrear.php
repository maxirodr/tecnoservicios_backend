<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Application\Pedidos;

use App\Modules\Identidad\Domain\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * RN-PED-09: quien carga suma seguidores al crear (después, cada uno se suma o se saca a sí mismo,
 * resuelta DU-P11): usuarios activos que no sean quien lo pide ni el propietario.
 */
final class SeguidoresAlCrear
{
    public const CAMPO = 'seguidores';

    /**
     * @param  list<int>  $ids
     * @return list<int>
     *
     * @throws ValidationException
     */
    public static function validar(array $ids, int $solicitanteId, int $propietarioId): array
    {
        $ids = array_values(array_unique($ids));

        if (in_array($solicitanteId, $ids, true) || in_array($propietarioId, $ids, true)) {
            throw ValidationException::withMessages([self::CAMPO => ['Quien lo pide y el propietario ya reciben los avisos: no hace falta sumarlos como seguidores.']]);
        }

        if ($ids !== [] && User::query()->whereKey($ids)->where('activo', true)->count() !== count($ids)) {
            throw ValidationException::withMessages([self::CAMPO => ['Elegí personas activas.']]);
        }

        return $ids;
    }
}
