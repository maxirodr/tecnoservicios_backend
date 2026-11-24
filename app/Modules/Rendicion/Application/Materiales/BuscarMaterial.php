<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Application\Materiales;

use App\Modules\Identidad\Domain\Enums\ClaveFuncion;
use App\Modules\Identidad\Domain\Enums\Nivel;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Obras\Contracts\ObrasConsulta;
use App\Modules\Rendicion\Application\Acceso\AccesoRendicion;
use App\Modules\Rendicion\Domain\Models\Material;
use App\Shared\Http\ApiResponse;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Trae un material solo si su obra está en el alcance de `control_cantidad` = editar (IDOR): uno de otra obra
 * fuera de alcance da 404, igual que uno que no existe (RN-REN-55).
 */
final readonly class BuscarMaterial
{
    public const NO_ENCONTRADO = 'No encontramos este material.';

    public function __construct(
        private ObrasConsulta $obras,
        private AccesoRendicion $acceso,
    ) {}

    /**
     * @throws HttpResponseException
     */
    public function editable(User $user, int $id): Material
    {
        $material = Material::query()->find($id);
        $obra = $material instanceof Material ? $this->obras->resumen($material->obra_id) : null;

        if (! $material instanceof Material || $obra === null || ! $this->acceso->puede($user, ClaveFuncion::ControlCantidad, Nivel::Editar, $obra)) {
            throw new HttpResponseException(ApiResponse::error(self::NO_ENCONTRADO, 404));
        }

        return $material;
    }
}
