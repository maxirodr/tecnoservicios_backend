<?php

declare(strict_types=1);

namespace App\Modules\Obras\Http\Requests\Concerns;

use App\Modules\Obras\Application\Presupuestos\DatosAprobacion;
use App\Modules\Obras\Application\Presupuestos\DatosPresupuesto;
use App\Modules\Obras\Domain\Enums\AprobacionTipo;
use App\Modules\Obras\Domain\Valores\NumeroPresupuesto;
use App\Modules\Obras\Domain\Valores\Revision;
use App\Modules\Obras\Domain\Valores\ValorInvalido;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Reglas y normalización de un presupuesto en el borde (RN-OBR-11, 14, 16), para el alta de obra
 * (`presupuesto`, `adicionales.N`) y para el adicional suelto (sin prefijo).
 */
trait ArmaPresupuestos
{
    /** @var array<string, array{numero: string, revision: string|null}> */
    private array $normalizados = [];

    /**
     * @return array<string, mixed>
     */
    protected function reglasPresupuesto(string $prefijo, bool $principal): array
    {
        return [
            self::clave($prefijo, 'numero') => ['required', 'string', 'max:60'],
            self::clave($prefijo, 'revision') => ['nullable', 'string', 'max:20'],
            self::clave($prefijo, 'oferta') => [$principal ? 'required' : 'nullable', 'integer'],
            self::clave($prefijo, 'descripcion') => ['nullable', 'string', 'max:200'],
            ...$this->reglasAprobacion(self::clave($prefijo, 'aprobacion')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function reglasAprobacion(string $campo): array
    {
        return [
            $campo => ['nullable', 'array'],
            "{$campo}.tipo" => ['nullable', Rule::enum(AprobacionTipo::class)],
            "{$campo}.numero" => ['nullable', 'string', 'max:60'],
            "{$campo}.adjunto" => ['nullable', 'integer'],
        ];
    }

    /**
     * N.º y revisión tolerantes: el número puede traer la revisión ("2-131-2026 REV01"); si choca con
     * la cargada, 422. En el principal la revisión es obligatoria.
     */
    protected function normalizarPresupuesto(Validator $validator, string $prefijo, bool $principal): void
    {
        $campoNumero = self::clave($prefijo, 'numero');
        $campoRevision = self::clave($prefijo, 'revision');

        if ($validator->errors()->hasAny([$campoNumero, $campoRevision])) {
            return;
        }

        try {
            $numero = NumeroPresupuesto::desde($this->string($campoNumero)->toString(), Carbon::now()->year);
        } catch (ValorInvalido $e) {
            $validator->errors()->add($campoNumero, $e->getMessage());

            return;
        }

        $revision = $numero->revisionEscrita;
        $escrita = $this->textoONull($campoRevision);

        if ($escrita !== null) {
            try {
                $cargada = Revision::desde($escrita);
            } catch (ValorInvalido $e) {
                $validator->errors()->add($campoRevision, $e->getMessage());

                return;
            }

            if ($revision !== null && ! $revision->equals($cargada)) {
                $validator->errors()->add($campoNumero, "El número trae {$revision} pero la revisión dice {$cargada}.");

                return;
            }

            $revision = $cargada;
        }

        if ($principal && $revision === null) {
            $validator->errors()->add($campoRevision, 'Completá este campo.');

            return;
        }

        $this->normalizados[$prefijo] = ['numero' => $numero->valor(), 'revision' => $revision?->valor];
    }

    /** RN-OBR-16: con tipo, al menos el número o el adjunto; sin tipo pero con alguno de los dos, es OC. */
    protected function validarAprobacion(Validator $validator, string $campo): void
    {
        if ($this->input("{$campo}.tipo") !== null && $this->textoONull("{$campo}.numero") === null && $this->input("{$campo}.adjunto") === null) {
            $validator->errors()->add("{$campo}.numero", 'Cargá el número o el archivo o link del documento de aprobación.');
        }
    }

    protected function datosAprobacion(string $campo): ?DatosAprobacion
    {
        $numero = $this->textoONull("{$campo}.numero");
        $adjunto = $this->enteroONull("{$campo}.adjunto");
        $tipo = $this->input("{$campo}.tipo");

        if ($tipo === null && $numero === null && $adjunto === null) {
            return null;
        }

        return new DatosAprobacion(is_string($tipo) ? AprobacionTipo::from($tipo) : AprobacionTipo::Oc, $numero, $adjunto);
    }

    protected function datosPresupuesto(string $prefijo): DatosPresupuesto
    {
        return new DatosPresupuesto(
            $this->normalizados[$prefijo]['numero'],
            $this->normalizados[$prefijo]['revision'],
            $this->enteroONull(self::clave($prefijo, 'oferta')),
            $this->textoONull(self::clave($prefijo, 'descripcion')),
            $this->datosAprobacion(self::clave($prefijo, 'aprobacion')),
        );
    }

    /**
     * @return array{numero: string, revision: string|null}|null
     */
    protected function normalizado(string $prefijo): ?array
    {
        return $this->normalizados[$prefijo] ?? null;
    }

    private static function clave(string $prefijo, string $campo): string
    {
        return $prefijo === '' ? $campo : "{$prefijo}.{$campo}";
    }
}
