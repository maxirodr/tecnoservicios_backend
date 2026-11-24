<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Http\Requests;

use App\Modules\Gastos\Application\Carga\DatosRenglon;
use App\Modules\Gastos\Application\Carga\MaterialNuevo;
use App\Shared\Domain\Importe;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Base de los FormRequest de Gastos: la forma en el borde, con los textos de la spec (gastos.md §5, §10).
 * La autorización va en las rutas y en los casos de uso (alcance), no en authorize(). Importes y
 * cantidades llegan como string decimal y se leen como string (ADR-008).
 */
abstract class GastosRequest extends FormRequest
{
    public const MAX_ADJUNTOS = 10;

    public const LARGO_NUMERO = 40;

    public const LARGO_TEXTO = 250;

    public const LARGO_RENDIDO_POR = 120;

    public const LARGO_MATERIAL = 150;

    public const LARGO_UNIDAD = 20;

    /** DECIMAL(14,3): 11 enteros. */
    public const MAXIMO_CANTIDAD = '99999999999.999';

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    protected function reglasDeRenglones(): array
    {
        return [
            'renglones.*' => ['array'],
            'renglones.*.fecha' => ['nullable', 'date_format:Y-m-d'],
            'renglones.*.concepto' => ['required', 'string', 'max:'.self::LARGO_TEXTO],
            'renglones.*.cantidad' => ['required', 'numeric', 'decimal:0,3', 'gte:-'.self::MAXIMO_CANTIDAD, 'lte:'.self::MAXIMO_CANTIDAD],
            'renglones.*.precio_unitario' => ['required', 'numeric', 'decimal:0,2', 'lte:'.Importe::MAXIMO],
            'renglones.*.obra_id' => ['present', 'nullable', 'integer'],
            'renglones.*.rubro' => ['nullable', 'string', 'max:40'],
            'renglones.*.material_id' => ['nullable', 'integer'],
            'renglones.*.material_nuevo' => ['nullable', 'array'],
            'renglones.*.material_nuevo.nombre' => ['required_with:renglones.*.material_nuevo', 'string', 'max:'.self::LARGO_MATERIAL],
            'renglones.*.material_nuevo.unidad' => ['required_with:renglones.*.material_nuevo', 'string', 'max:'.self::LARGO_UNIDAD],
            'renglones.*.detalle' => ['nullable', 'string', 'max:'.self::LARGO_TEXTO],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => 'Completá este campo.',
            'present' => 'Completá este campo.',
            'string' => 'Tiene que ser texto.',
            'max' => 'Es demasiado largo (máximo :max caracteres).',
            'max.array' => 'Son demasiados (máximo :max).',
            'integer' => 'Tiene que ser un número entero.',
            'numeric' => 'Tiene que ser un número.',
            'array' => 'Tiene que ser una lista.',
            'in' => 'El valor no es válido.',
            'boolean' => 'El valor no es válido.',
            'date_format' => 'Escribí la fecha como dd/mm/aaaa, por ejemplo 31/12/2099.',
            'distinct' => 'Está repetido.',
            'renglones.*.concepto.required' => 'Renglón :position: escribí el concepto.',
            'renglones.*.cantidad.required' => 'Renglón :position: poné la cantidad.',
            'renglones.*.cantidad.numeric' => 'Renglón :position: poné la cantidad.',
            'renglones.*.cantidad.decimal' => 'Renglón :position: hasta 3 decimales en la cantidad.',
            'renglones.*.cantidad.gte' => 'Renglón :position: la cantidad es demasiado grande.',
            'renglones.*.cantidad.lte' => 'Renglón :position: la cantidad es demasiado grande.',
            'renglones.*.precio_unitario.required' => 'Renglón :position: poné el precio unitario.',
            'renglones.*.precio_unitario.numeric' => 'Renglón :position: poné el precio unitario.',
            'renglones.*.precio_unitario.decimal' => 'Renglón :position: hasta 2 decimales en el precio.',
            'renglones.*.precio_unitario.lte' => 'Renglón :position: el total del renglón es demasiado grande.',
            'renglones.*.obra_id.present' => "Renglón :position: elegí la obra o '(sin obra)'.",
            'renglones.*.obra_id.integer' => "Renglón :position: elegí la obra o '(sin obra)'.",
            'renglones.*.fecha.date_format' => 'Renglón :position: escribí la fecha como dd/mm/aaaa, por ejemplo 31/12/2099.',
            'renglones.*.material_nuevo.nombre.required_with' => 'Renglón :position: escribí el material.',
            'renglones.*.material_nuevo.unidad.required_with' => 'Renglón :position: poné la unidad.',
        ];
    }

    /**
     * @return list<DatosRenglon>
     */
    protected function renglones(): array
    {
        $renglones = $this->input('renglones');

        if (! is_array($renglones)) {
            return [];
        }

        $datos = [];

        foreach (array_values($renglones) as $posicion => $r) {
            $r = is_array($r) ? $r : [];
            $nuevo = isset($r['material_nuevo']) && is_array($r['material_nuevo']) ? $r['material_nuevo'] : null;

            $datos[] = new DatosRenglon(
                $posicion,
                self::entero($r['id'] ?? null),
                self::texto($r['fecha'] ?? null),
                (string) self::texto($r['concepto'] ?? null),
                self::decimal($r['cantidad'] ?? null),
                self::decimal($r['precio_unitario'] ?? null),
                self::entero($r['obra_id'] ?? null),
                self::texto($r['rubro'] ?? null),
                self::entero($r['material_id'] ?? null),
                $nuevo === null ? null : new MaterialNuevo((string) self::texto($nuevo['nombre'] ?? null), (string) self::texto($nuevo['unidad'] ?? null)),
                self::texto($r['detalle'] ?? null),
            );
        }

        return $datos;
    }

    protected static function texto(mixed $valor): ?string
    {
        return is_string($valor) && trim($valor) !== '' ? trim($valor) : null;
    }

    protected static function entero(mixed $valor): ?int
    {
        return is_numeric($valor) ? (int) $valor : null;
    }

    /**
     * @return numeric-string
     */
    private static function decimal(mixed $valor): string
    {
        $texto = is_scalar($valor) ? trim((string) $valor) : '0';

        return is_numeric($texto) ? $texto : '0';
    }

    /**
     * @return list<int>
     */
    protected function ids(string $campo): array
    {
        $valores = $this->input($campo);

        return is_array($valores)
            ? array_values(array_map(static fn (mixed $v): int => is_numeric($v) ? (int) $v : 0, $valores))
            : [];
    }
}
