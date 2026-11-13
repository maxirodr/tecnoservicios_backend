<?php

declare(strict_types=1);

namespace App\Modules\Proveedores\Http\Requests;

use App\Modules\Proveedores\Domain\Cuit;
use App\Modules\Rendicion\Domain\Models\Rubro;
use Closure;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * `POST /api/proveedores` y `PATCH /api/proveedores/{id}` (W-38, proveedores.md §4.1). Solo el nombre
 * es obligatorio en el alta (RN-PRV-05); en la edición todo es opcional y se manda solo lo que cambia.
 * Lo que no es un campo del proveedor (`adjuntos`, `factura_url`…) se ignora (§4.2).
 */
final class GuardarProveedorRequest extends ProveedoresRequest
{
    private const CAMPOS = ['nombre', 'cuit', 'telefono', 'email', 'rubro_habitual'];

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $nombre = $this->isMethod('post') ? ['required'] : ['sometimes', 'required'];

        return [
            'nombre' => [...$nombre, 'string', 'max:150'],
            'cuit' => ['nullable', 'string', 'max:20', $this->cuitValido(...)],
            'telefono' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'string', 'email', 'max:190'],
            'rubro_habitual' => ['nullable', 'string', Rule::exists(Rubro::class, 'clave')],
        ];
    }

    /**
     * Columnas a guardar, solo de los campos que vinieron: CUIT en 11 dígitos, mail en minúsculas,
     * rubro habitual por id, vacíos como null.
     *
     * @return array<string, mixed>
     */
    public function datos(): array
    {
        $datos = [];
        foreach (self::CAMPOS as $campo) {
            if ($this->exists($campo)) {
                $valor = $this->textoOpcional($campo);
                $datos[$campo === 'rubro_habitual' ? 'rubro_habitual_id' : $campo] = $valor === null ? null : $this->columna($campo, $valor);
            }
        }

        return $datos;
    }

    private function columna(string $campo, string $valor): string|int|null
    {
        return match ($campo) {
            'cuit' => Cuit::desde($valor)?->digitos,
            'rubro_habitual' => (int) Rubro::query()->where('clave', $valor)->valueOrFail('id'),
            default => $valor,
        };
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('nombre'))) {
            $this->merge(['nombre' => Str::squish($this->string('nombre')->toString())]);
        }

        if (is_string($this->input('email'))) {
            $this->merge(['email' => Str::lower(trim($this->string('email')->toString()))]);
        }
    }

    /** RN-PRV-06: 11 dígitos (con o sin guiones o espacios) y dígito verificador que da. */
    private function cuitValido(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && Cuit::desde($value) === null) {
            $fail('El CUIT no es válido. Revisá los números.');
        }
    }
}
