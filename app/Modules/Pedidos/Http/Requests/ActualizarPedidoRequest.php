<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Http\Requests;

use App\Modules\Identidad\Domain\Enums\Prioridad;
use App\Modules\Pedidos\Application\Pedidos\CambiosPedido;
use App\Shared\Concurrencia\VersionDeFila;
use Carbon\CarbonImmutable;
use Illuminate\Validation\Rule;

/**
 * `PATCH /api/pedidos/{id}` (W-32, RN-PED-28): solo los campos que vienen. Solicitante, estado,
 * propietario, sector, motivo y cierre no se leen (tienen sus acciones). `adjuntos` = la lista completa:
 * los que no vienen se quitan. `updated_at` = la versión que se abrió (409 si cambió).
 */
final class ActualizarPedidoRequest extends PedidosRequest
{
    public const CAMPOS_TEXTO = ['tarea', 'con_quien', 'aclaraciones', 'fecha_pedido', 'fecha_entrega', 'periodo_inicio', 'periodo_fin'];

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tarea' => ['sometimes', 'required', 'string', 'max:'.self::LARGO_TAREA],
            'obra_id' => ['sometimes', 'nullable', 'integer'],
            'fecha_entrega' => ['sometimes', 'required', 'date_format:Y-m-d'],
            'fecha_pedido' => ['sometimes', 'required', 'date_format:Y-m-d'],
            'periodo_inicio' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'periodo_fin' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'con_quien' => ['sometimes', 'nullable', 'string', 'max:'.self::LARGO_CON_QUIEN],
            'aclaraciones' => ['sometimes', 'nullable', 'string', 'max:'.self::LARGO_TEXTO],
            'adjuntos' => ['sometimes', 'array', 'max:'.self::MAX_ADJUNTOS],
            'adjuntos.*' => ['integer', 'distinct'],
            'prioridad' => ['sometimes', 'nullable', Rule::enum(Prioridad::class)],
            'usar_prioridad_calculada' => ['sometimes', 'boolean'],
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
            'tarea.required' => 'Escribí la tarea.',
            'fecha_entrega.required' => 'Poné la fecha de entrega.',
            'fecha_pedido.required' => 'Poné la fecha de pedido.',
        ];
    }

    public function versionEsperada(): ?CarbonImmutable
    {
        return VersionDeFila::desde($this->input(VersionDeFila::CAMPO));
    }

    public function cambios(): CambiosPedido
    {
        $textos = [];

        foreach (self::CAMPOS_TEXTO as $campo) {
            if ($this->exists($campo)) {
                $textos[$campo] = $this->textoONull($campo);
            }
        }

        return new CambiosPedido(
            $textos,
            $this->exists('obra_id'),
            $this->enteroONull('obra_id'),
            $this->exists('adjuntos') ? $this->ids('adjuntos') : null,
            $this->filled('prioridad') ? Prioridad::from($this->string('prioridad')->toString()) : null,
            $this->boolean('usar_prioridad_calculada'),
        );
    }
}
