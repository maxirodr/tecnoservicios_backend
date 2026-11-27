<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Application\Acceso;

use App\Modules\Identidad\Contracts\Permisos;
use App\Modules\Identidad\Domain\Enums\Alcance;
use App\Modules\Identidad\Domain\Enums\ClaveFuncion;
use App\Modules\Identidad\Domain\Enums\Nivel;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Obras\Contracts\ObraResumen;
use App\Modules\Obras\Contracts\ObrasConsulta;
use App\Shared\Http\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Permisos de la rendición y del control por cantidad (rendicion.md §7, RN-REN-55…58), siempre por la matriz
 * (ADR-006) con su alcance: "solo sus obras" = obras donde es responsable. Una obra fuera de alcance da 404
 * (no se confirma que exista); sin la función, 403.
 */
final readonly class AccesoRendicion
{
    public const SIN_RENDICION = 'No tenés acceso a la rendición.';

    public const OBRA_NO_ENCONTRADA = 'No encontramos esta obra.';

    public function __construct(
        private Permisos $permisos,
        private ObrasConsulta $obras,
    ) {}

    public function puede(User $user, ClaveFuncion $funcion, Nivel $nivel, ObraResumen $obra): bool
    {
        return $this->permisos->puede($user, $funcion->value, $nivel)
            && ($this->permisos->alcance($user, $funcion->value) === Alcance::Todas || $obra->responsableId === $user->id);
    }

    /** El parte de horas por persona, con nombre y valor hora: `gastos = editar` sobre la obra (RN-REN-63, DU-C02). */
    public function detallePorPersona(User $user, ObraResumen $obra): bool
    {
        return $this->puede($user, ClaveFuncion::Gastos, Nivel::Editar, $obra);
    }

    public function tiene(User $user, ClaveFuncion $funcion, Nivel $nivel): bool
    {
        return $this->permisos->puede($user, $funcion->value, $nivel);
    }

    /**
     * La celda `rendicion` ≥ ver, sin mirar la obra: 403 "No tenés acceso a la rendición.".
     *
     * @throws HttpResponseException
     */
    public function exigirRendicion(User $user): void
    {
        if (! $this->tiene($user, ClaveFuncion::Rendicion, Nivel::Ver)) {
            throw new HttpResponseException(ApiResponse::error(self::SIN_RENDICION, 403));
        }
    }

    /**
     * W-11, W-36, W-37, W-44: `rendicion` ≥ ver para esa obra.
     *
     * @throws HttpResponseException
     */
    public function obraDeRendicion(User $user, int $obraId): ObraResumen
    {
        $this->exigirRendicion($user);

        return $this->visible($obraId, fn (ObraResumen $obra): bool => $this->puede($user, ClaveFuncion::Rendicion, Nivel::Ver, $obra));
    }

    /**
     * W-48 y la lista de materiales: `control_cantidad` con ese nivel para esa obra.
     *
     * @throws AuthorizationException|HttpResponseException
     */
    public function obraDeControl(User $user, int $obraId, Nivel $nivel): ObraResumen
    {
        if (! $this->tiene($user, ClaveFuncion::ControlCantidad, $nivel)) {
            throw new AuthorizationException;
        }

        return $this->visible($obraId, fn (ObraResumen $obra): bool => $this->puede($user, ClaveFuncion::ControlCantidad, $nivel, $obra));
    }

    /**
     * Opciones de materiales para W-10 y W-46 (§7.2): `control_cantidad` ≥ ver, `gastos` = editar o
     * `rendicion` = editar, cualquiera, para esa obra.
     *
     * @throws AuthorizationException|HttpResponseException
     */
    public function obraParaOpciones(User $user, int $obraId): ObraResumen
    {
        $celdas = [[ClaveFuncion::ControlCantidad, Nivel::Ver], [ClaveFuncion::Gastos, Nivel::Editar], [ClaveFuncion::Rendicion, Nivel::Editar]];
        $conNivel = array_filter($celdas, fn (array $c): bool => $this->tiene($user, $c[0], $c[1]));

        if ($conNivel === []) {
            throw new AuthorizationException;
        }

        return $this->visible($obraId, function (ObraResumen $obra) use ($user, $conNivel): bool {
            foreach ($conNivel as [$funcion, $nivel]) {
                if ($this->puede($user, $funcion, $nivel, $obra)) {
                    return true;
                }
            }

            return false;
        });
    }

    /**
     * W-12: null = todas; con "solo sus obras", las que tiene como responsable.
     *
     * @return list<int>|null
     *
     * @throws HttpResponseException
     */
    public function obrasDeRendicion(User $user): ?array
    {
        if (! $this->tiene($user, ClaveFuncion::Rendicion, Nivel::Ver)) {
            throw new HttpResponseException(ApiResponse::error(self::SIN_RENDICION, 403));
        }

        return $this->permisos->alcance($user, ClaveFuncion::Rendicion->value) === Alcance::Todas
            ? null
            : $this->obras->obrasDondeEsResponsable($user->id);
    }

    /**
     * @param  callable(ObraResumen): bool  $alcanza
     *
     * @throws HttpResponseException
     */
    private function visible(int $obraId, callable $alcanza): ObraResumen
    {
        $obra = $this->obras->resumen($obraId);

        if ($obra === null || ! $alcanza($obra)) {
            throw new HttpResponseException(ApiResponse::error(self::OBRA_NO_ENCONTRADA, 404));
        }

        return $obra;
    }
}
