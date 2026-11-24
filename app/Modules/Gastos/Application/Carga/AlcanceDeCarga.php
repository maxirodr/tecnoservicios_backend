<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Application\Carga;

use App\Modules\Gastos\Application\Acceso\AccesoGasto;
use App\Modules\Gastos\Application\Acceso\BuscarGasto;
use App\Modules\Gastos\Domain\Enums\Origen;
use App\Modules\Identidad\Domain\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Quién carga qué y dónde (gastos.md §7, §8.1, ADR-006): W-10 y W-35 con `gastos = editar`; W-46 con
 * `rendicion = editar` (retiros, también `control_cantidad = editar`). Con "solo sus obras" imputa solo a
 * sus obras y nunca a "(sin obra)" (RN-GAS-49); en W-46, solo en la rendición de sus obras.
 */
final readonly class AlcanceDeCarga
{
    public const SIN_PERMISO = 'Solo administración y compras cargan gastos.';

    public const SOLO_SUS_OBRAS = 'Con tu permiso solo podés imputar a tus obras.';

    public const RENDICION_AJENA = 'No podés cargar en esta rendición.';

    public function __construct(private AccesoGasto $acceso) {}

    /**
     * @param  list<int|null>  $obraIds  la obra de cada renglón (null = sin obra)
     *
     * @throws HttpResponseException 403
     */
    public function exigir(User $user, Origen $origen, array $obraIds): void
    {
        $desdeLaRendicion = $origen->seCargaDesdeLaRendicion();
        BuscarGasto::exigir($this->acceso->cargaOrigen($user, $origen), $desdeLaRendicion ? self::RENDICION_AJENA : self::SIN_PERMISO);

        $permitidas = $this->acceso->obrasParaCargar($user, $origen);

        if ($permitidas === null) {
            return;
        }

        foreach ($obraIds as $obraId) {
            BuscarGasto::exigir(
                $obraId !== null && in_array($obraId, $permitidas, true),
                $desdeLaRendicion ? self::RENDICION_AJENA : self::SOLO_SUS_OBRAS,
            );
        }
    }
}
