<?php

declare(strict_types=1);

namespace App\Modules\Obras\Http\Requests;

use App\Modules\Obras\Application\Obras\DatosObra;
use App\Modules\Obras\Domain\Enums\TipoContacto;
use App\Modules\Obras\Http\Requests\Concerns\ArmaPresupuestos;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * `POST /api/obras` (ObraAlta, obras.md §4.1 y §10.1). Obligatorios de la tabla del cap. 6 en el
 * principal (resuelta DU-A14). `estado` se ignora: la obra siempre nace Confirmada (HU-012 c. 5).
 */
final class CrearObraRequest extends ObrasRequest
{
    use ArmaPresupuestos;

    public const MAX_ADICIONALES = 10;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $reglas = [
            ...ReglasObra::datos(true),
            'responsable_id' => ['required', 'integer', Rule::exists('users', 'id')->where('activo', true)],
            ...$this->reglasPresupuesto('presupuesto', true),
            'presupuesto' => ['required', 'array'],
            'adicionales' => ['nullable', 'array', 'max:'.self::MAX_ADICIONALES],
            'contactos' => ['required', 'array'],
            ...ReglasObra::contactos(true),
        ];

        foreach (array_keys($this->adicionales()) as $indice) {
            $reglas = [...$reglas, ...$this->reglasPresupuesto("adicionales.{$indice}", false)];
        }

        return $reglas;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validarFechas($validator);
            $this->normalizarPresupuesto($validator, 'presupuesto', true);
            $this->validarAprobacion($validator, 'presupuesto.aprobacion');

            foreach (array_keys($this->adicionales()) as $indice) {
                $this->normalizarPresupuesto($validator, "adicionales.{$indice}", false);
                $this->validarAprobacion($validator, "adicionales.{$indice}.aprobacion");
            }
        });
    }

    public function datos(): DatosObra
    {
        $contactos = [];

        foreach (TipoContacto::cases() as $tipo) {
            $contactos[$tipo->value] = [
                'nombre' => $this->string("contactos.{$tipo->value}.nombre")->trim()->toString(),
                'email' => $this->string("contactos.{$tipo->value}.email")->trim()->lower()->toString(),
                'telefono' => $this->string("contactos.{$tipo->value}.telefono")->trim()->toString(),
            ];
        }

        return new DatosObra(
            $this->string('nombre_trabajo')->trim()->toString(),
            $this->string('cliente')->squish()->toString(),
            $this->textoONull('planta'),
            $this->integer('responsable_id'),
            $this->textoONull('fecha_inicio_prevista'),
            $this->textoONull('fecha_fin_prevista'),
            $this->textoONull('carpeta_drive_url'),
            $this->datosPresupuesto('presupuesto'),
            array_map(fn (int $i) => $this->datosPresupuesto("adicionales.{$i}"), array_keys($this->adicionales())),
            ['solicitante' => $contactos['solicitante'], 'comprador' => $contactos['comprador']],
        );
    }

    /** F-23: si están las dos, el fin previsto no es anterior al inicio. */
    private function validarFechas(Validator $validator): void
    {
        $inicio = $this->textoONull('fecha_inicio_prevista');
        $fin = $this->textoONull('fecha_fin_prevista');

        if ($inicio !== null && $fin !== null && ! $validator->errors()->hasAny(['fecha_inicio_prevista', 'fecha_fin_prevista']) && $fin < $inicio) {
            $validator->errors()->add('fecha_fin_prevista', 'La fecha de fin no puede ser anterior a la de inicio.');
        }
    }

    /**
     * @return array<int, mixed>
     */
    private function adicionales(): array
    {
        $adicionales = $this->input('adicionales');

        return is_array($adicionales) ? array_values($adicionales) : [];
    }
}
