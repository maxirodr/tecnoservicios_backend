<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Http\Requests;

use App\Modules\Rendicion\Application\Materiales\DatosMaterial;
use App\Modules\Rendicion\Http\Rules\DecimalNoNegativo;
use App\Modules\Rendicion\Infrastructure\MaterialesDeObraEloquent;
use App\Shared\Concurrencia\VersionDeFila;
use Carbon\CarbonImmutable;

/**
 * `POST /api/obras/{id}/materiales` y `PATCH /api/materiales/{id}` (W-48, rendicion.md §4.2 y §12.2): Material ·
 * Unidad · Cantidad presupuestada · Precio unitario (opcional) · Presupuesto. En la corrección todo es opcional
 * y viaja `updated_at` (409, RN-REN-61); la cantidad no se puede vaciar.
 */
class GuardarMaterialRequest extends RendicionRequest
{
    public const FALTA_CANTIDAD = 'Poné la cantidad presupuestada.';

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $obligatorio = $this->isMethod('PATCH') ? ['sometimes', 'required'] : ['required'];

        return [
            'nombre' => [...$obligatorio, 'string', 'max:'.MaterialesDeObraEloquent::NOMBRE_MAXIMO],
            'unidad' => [...$obligatorio, 'string', 'max:'.MaterialesDeObraEloquent::UNIDAD_MAXIMA],
            'cantidad_presupuestada' => [...$obligatorio, DecimalNoNegativo::cantidad()],
            'precio_presupuestado' => ['sometimes', 'nullable', DecimalNoNegativo::precio()],
            'presupuesto_id' => ['sometimes', 'nullable', 'integer'],
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
            'nombre.required' => 'Escribí el material.',
            'unidad.required' => 'Escribí la unidad.',
            'cantidad_presupuestada.required' => self::FALTA_CANTIDAD,
        ];
    }

    public function datos(): DatosMaterial
    {
        $presupuesto = $this->input('presupuesto_id');

        return new DatosMaterial(
            $this->textoONull('nombre'),
            $this->textoONull('unidad'),
            $this->decimal('cantidad_presupuestada', 3),
            $this->decimal('precio_presupuestado', 2),
            $presupuesto === null || $presupuesto === '' ? null : (int) $presupuesto,
            array_values(array_filter(DatosMaterial::CAMPOS, fn (string $campo): bool => $this->exists($campo))),
        );
    }

    public function versionEsperada(): ?CarbonImmutable
    {
        return VersionDeFila::desde($this->input(VersionDeFila::CAMPO));
    }

    /**
     * @return numeric-string|null
     */
    private function decimal(string $campo, int $escala): ?string
    {
        $texto = DecimalNoNegativo::texto($this->input($campo));

        return $texto === '' || ! is_numeric($texto) ? null : bcadd($texto, '0', $escala);
    }
}
