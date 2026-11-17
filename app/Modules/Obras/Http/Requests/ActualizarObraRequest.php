<?php

declare(strict_types=1);

namespace App\Modules\Obras\Http\Requests;

use App\Modules\Obras\Application\Obras\ActualizarObraAction;
use App\Modules\Obras\Application\Presupuestos\DatosAprobacion;
use App\Modules\Obras\Domain\Enums\TipoContacto;
use App\Modules\Obras\Domain\Models\ObraContacto;
use App\Modules\Obras\Http\Requests\Concerns\EditaPresupuesto;
use App\Shared\Concurrencia\VersionDeFila;
use Carbon\CarbonImmutable;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * `PATCH /api/obras/{id}` (W-23): campos de ObraAlta + contactos y, opcional, `presupuesto_principal`
 * con los mismos campos y reglas que `PATCH /api/presupuestos/{id}` (PU-101: todo en una transacción y
 * bajo la versión de la obra). Mismas validaciones que el alta (HU-016 c. 6). `estado` se ignora
 * (HU-016 c. 4, RN-OBR-22). Los adicionales se editan aparte.
 */
final class ActualizarObraRequest extends ObrasRequest
{
    use EditaPresupuesto;

    private const PRINCIPAL = ActualizarObraAction::PREFIJO_PRINCIPAL;

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
            self::PRINCIPAL => ['sometimes', 'array'],
            ...$this->reglasEdicionPresupuesto(self::PRINCIPAL),
            ...VersionDeFila::reglas(),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->exists(self::PRINCIPAL)) {
                $this->validarEdicionPresupuesto($validator, self::PRINCIPAL);
            }
        });
    }

    /** `updated_at` que tenía la obra al abrir el formulario (PU-002). */
    public function versionEsperada(): ?CarbonImmutable
    {
        return VersionDeFila::desde($this->input(VersionDeFila::CAMPO));
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
     * Cambios del presupuesto principal; vacío si no vino `presupuesto_principal`.
     *
     * @return array{numero?: string, revision?: string|null, descripcion?: string|null, oferta?: int|null, aprobacion?: DatosAprobacion|null}
     */
    public function presupuestoPrincipal(): array
    {
        return $this->exists(self::PRINCIPAL) ? $this->datosEdicionPresupuesto(self::PRINCIPAL) : [];
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
