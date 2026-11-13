<?php

declare(strict_types=1);

namespace App\Modules\Obras\Http\Requests;

use App\Modules\Obras\Domain\Enums\TipoContacto;
use App\Modules\Obras\Domain\Models\ObraContacto;
use Illuminate\Validation\Rule;

/**
 * `PATCH /api/obras/{id}` (W-23): campos de ObraAlta sin presupuestos (se editan aparte) + contactos.
 * Mismas validaciones que el alta (HU-016 c. 6). `estado` se ignora (HU-016 c. 4, RN-OBR-22).
 */
final class ActualizarObraRequest extends ObrasRequest
{
    private const CAMPOS = [
        'nombre_trabajo', 'cliente', 'planta', 'fecha_inicio_prevista', 'fecha_fin_prevista', 'carpeta_drive_url',
    ];

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...ReglasObra::datos(false),
            // Si se cambia, tiene que estar activo: lo verifica el caso de uso (caso borde 18).
            'responsable_id' => ['sometimes', 'integer', Rule::exists('users', 'id')],
            'contactos' => ['sometimes', 'array'],
            ...ReglasObra::contactos(false),
        ];
    }

    /**
     * @return array<string, mixed> solo los campos que vinieron
     */
    public function datos(): array
    {
        $datos = [];

        foreach (self::CAMPOS as $campo) {
            if ($this->exists($campo)) {
                $datos[$campo] = $this->textoONull($campo);
            }
        }

        if ($this->exists('cliente')) {
            $datos['cliente'] = $this->string('cliente')->squish()->toString();
        }

        if ($this->exists('responsable_id')) {
            $datos['responsable_id'] = $this->integer('responsable_id');
        }

        return $datos;
    }

    /**
     * @return array<string, array<string, string>> tipo => campo => valor
     */
    public function contactos(): array
    {
        $contactos = [];

        foreach (TipoContacto::cases() as $tipo) {
            foreach (ObraContacto::CAMPOS as $campo) {
                $clave = "contactos.{$tipo->value}.{$campo}";

                if ($this->exists($clave)) {
                    $valor = $this->string($clave)->trim();
                    $contactos[$tipo->value][$campo] = ($campo === 'email' ? $valor->lower() : $valor)->toString();
                }
            }
        }

        return $contactos;
    }
}
