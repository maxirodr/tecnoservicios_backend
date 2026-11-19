<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Http\Requests;

use App\Modules\Pedidos\Application\Equipos\DatosEquipo;
use App\Modules\Pedidos\Domain\Enums\EquipoAprobacion;
use App\Modules\Pedidos\Domain\Enums\EquipoEstado;
use App\Shared\Concurrencia\VersionDeFila;
use Carbon\CarbonImmutable;
use Illuminate\Validation\Rule;

/**
 * Alta (`POST`) y edición (`PATCH`) de un equipo en reparación (§11.2, W-49). En el alta, equipo y falla o
 * trabajo son obligatorios (resuelta DU-P18); en la edición todo es opcional y solo cambia lo que viene.
 * Un estado o una aprobación fuera de sus valores: 422 (HU-050 c3).
 */
final class EquipoRequest extends PedidosRequest
{
    private const CAMPOS_TEXTO = ['equipo', 'codigo', 'trabajo', 'taller', 'estado', 'aprobacion', 'fecha', 'notas'];

    private const LARGO_NOMBRE = 150;

    private const LARGO_CODIGO = 40;

    private const LARGO_TRABAJO = 2000;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $obligatorio = $this->isMethod('POST') ? 'required' : 'sometimes';

        return [
            'equipo' => [$obligatorio, 'required', 'string', 'max:'.self::LARGO_NOMBRE],
            'codigo' => ['sometimes', 'nullable', 'string', 'max:'.self::LARGO_CODIGO],
            'trabajo' => [$obligatorio, 'required', 'string', 'max:'.self::LARGO_TRABAJO],
            'taller' => ['sometimes', 'nullable', 'string', 'max:'.self::LARGO_NOMBRE],
            'estado' => ['sometimes', 'required', Rule::enum(EquipoEstado::class)],
            'aprobacion' => ['sometimes', 'required', Rule::enum(EquipoAprobacion::class)],
            'fecha' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'notas' => ['sometimes', 'nullable', 'string', 'max:'.self::LARGO_TRABAJO],
            DatosEquipo::INVENTARIO => ['sometimes', 'nullable', 'integer', 'min:1'],
            DatosEquipo::PRESUPUESTO => ['sometimes', 'nullable', 'integer', 'min:1'],
            ...VersionDeFila::reglas(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'equipo.required' => 'Escribí el equipo.',
            'trabajo.required' => 'Contá la falla o el trabajo.',
        ];
    }

    public function datos(): DatosEquipo
    {
        $campos = [];

        foreach (self::CAMPOS_TEXTO as $campo) {
            if ($this->exists($campo)) {
                $campos[$campo] = $this->textoONull($campo);
            }
        }

        $adjuntos = [];

        foreach ([DatosEquipo::INVENTARIO, DatosEquipo::PRESUPUESTO] as $campo) {
            if ($this->exists($campo)) {
                $adjuntos[$campo] = $this->enteroONull($campo);
            }
        }

        return new DatosEquipo($campos, $adjuntos);
    }

    public function versionEsperada(): ?CarbonImmutable
    {
        return VersionDeFila::desde($this->input(VersionDeFila::CAMPO));
    }
}
