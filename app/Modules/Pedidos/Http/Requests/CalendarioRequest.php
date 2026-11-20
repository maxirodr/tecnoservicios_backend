<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Http\Requests;

use App\Modules\Pedidos\Application\Lecturas\CalendarioDePedidos;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Validation\Rule;

/**
 * `GET /api/pedidos/calendario?desde=&hasta=&de=mias|mi_gente&obra_id=` (§11.1): un mes o una semana de
 * W-03, con un rango de hasta 62 días (un mes con las semanas que lo completan).
 */
final class CalendarioRequest extends PedidosRequest
{
    use FiltraPorObra;

    public const MAX_DIAS = 62;

    private const FECHA = 'date_format:Y-m-d';

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'desde' => ['required', self::FECHA],
            'hasta' => ['required', self::FECHA, 'after_or_equal:desde', $this->rangoMaximo(...)],
            'de' => ['nullable', Rule::in([CalendarioDePedidos::MIAS, CalendarioDePedidos::MI_GENTE])],
            'obra_id' => self::reglaDeObra(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'date_format' => 'Escribí la fecha como AAAA-MM-DD.',
            'hasta.after_or_equal' => 'El fin no puede ser anterior al inicio.',
        ];
    }

    public function desde(): string
    {
        return (string) $this->textoONull('desde');
    }

    public function hasta(): string
    {
        return (string) $this->textoONull('hasta');
    }

    public function de(): string
    {
        return $this->textoONull('de') === CalendarioDePedidos::MI_GENTE ? CalendarioDePedidos::MI_GENTE : CalendarioDePedidos::MIAS;
    }

    private function rangoMaximo(string $atributo, mixed $hasta, Closure $falla): void
    {
        $desde = $this->input('desde');

        if (! is_string($desde) || ! is_string($hasta)
            || ! CarbonImmutable::hasFormat($desde, 'Y-m-d') || ! CarbonImmutable::hasFormat($hasta, 'Y-m-d')) {
            return;
        }

        if (CarbonImmutable::parse($desde)->diffInDays(CarbonImmutable::parse($hasta)) >= self::MAX_DIAS) {
            $falla('Elegí un rango de hasta '.self::MAX_DIAS.' días.');
        }
    }
}
