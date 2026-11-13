<?php

declare(strict_types=1);

namespace App\Modules\Obras\Application\Obras;

use App\Modules\Identidad\Domain\Enums\ClaveFuncion;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Obras\Application\Acceso\AccesoObra;
use App\Modules\Obras\Domain\Models\Obra;
use App\Modules\Obras\Domain\Models\ObraContacto;
use App\Shared\Actividad\RegistroActividad;
use Illuminate\Database\Connection;
use Illuminate\Validation\ValidationException;

/**
 * F-20 / HU-016: editar los datos de la obra y sus contactos. Cada dato que cambia queda en el
 * historial con antes → después; guardar sin cambios no registra (RN-OBR-26). El estado no se edita
 * acá (RN-OBR-22): el FormRequest no lo deja pasar.
 */
final readonly class ActualizarObraAction
{
    public const ACCION = 'editado';

    private const CAMPOS = [
        'nombre_trabajo', 'cliente', 'planta', 'fecha_inicio_prevista', 'fecha_fin_prevista', 'carpeta_drive_url',
    ];

    public function __construct(
        private Connection $db,
        private AccesoObra $acceso,
        private RegistroActividad $actividad,
    ) {}

    /**
     * @param  array<string, mixed>  $datos  solo los campos que vinieron
     * @param  array<string, array<string, string>>  $contactos  tipo => campo => valor
     *
     * @throws ValidationException
     */
    public function handle(User $actor, Obra $obra, array $datos, array $contactos): Obra
    {
        return $this->db->transaction(function () use ($actor, $obra, $datos, $contactos): Obra {
            $obra->load(['responsable', 'contactos']);
            $cambios = [
                ...$this->cambiarResponsable($actor, $obra, $datos),
                ...$this->cambiarDatos($obra, $datos),
                ...$this->cambiarContactos($obra, $contactos),
            ];

            if ($cambios !== []) {
                $this->actividad->registrar($obra, self::ACCION, $cambios);
            }

            return $obra;
        });
    }

    /**
     * @param  array<string, mixed>  $datos
     * @return array<string, array{antes: mixed, despues: mixed}>
     */
    private function cambiarDatos(Obra $obra, array $datos): array
    {
        $antes = HistorialDeCampos::valores($obra, self::CAMPOS);
        $obra->fill(array_intersect_key($datos, array_flip(self::CAMPOS)));
        $despues = HistorialDeCampos::valores($obra, self::CAMPOS);

        $inicio = $obra->fecha_inicio_prevista;
        $fin = $obra->fecha_fin_prevista;

        if ($inicio !== null && $fin !== null && $fin->lt($inicio)) {
            throw ValidationException::withMessages(['fecha_fin_prevista' => ['La fecha de fin no puede ser anterior a la de inicio.']]);
        }

        $obra->save();

        return HistorialDeCampos::diferencias($antes, $despues);
    }

    /**
     * Si se cambia, el nuevo responsable tiene que estar activo; uno que quedó inactivo sigue
     * mientras no se toque el campo (obras.md §9 caso 18).
     *
     * @param  array<string, mixed>  $datos
     * @return array<string, array{antes: mixed, despues: mixed}>
     */
    private function cambiarResponsable(User $actor, Obra $obra, array $datos): array
    {
        $nuevoId = $datos['responsable_id'] ?? null;

        if (! is_int($nuevoId) || $nuevoId === $obra->responsable_id) {
            return [];
        }

        $nuevo = User::query()->whereKey($nuevoId)->where('activo', true)->first();

        if (! $nuevo instanceof User) {
            throw ValidationException::withMessages(['responsable_id' => ['Elegí una persona activa.']]);
        }

        if ($this->acceso->soloPropias($actor, ClaveFuncion::Obras) && $nuevo->id !== $actor->id) {
            throw ValidationException::withMessages(['responsable_id' => ['Solo podés ponerte a vos como responsable de la obra.']]);
        }

        $antes = $obra->responsable->nombre;
        $obra->responsable()->associate($nuevo);

        return ['responsable' => ['antes' => $antes, 'despues' => $nuevo->nombre]];
    }

    /**
     * @param  array<string, array<string, string>>  $contactos
     * @return array<string, array{antes: mixed, despues: mixed}>
     */
    private function cambiarContactos(Obra $obra, array $contactos): array
    {
        $cambios = [];

        foreach ($obra->contactos as $contacto) {
            $nuevos = array_intersect_key($contactos[$contacto->tipo->value] ?? [], array_flip(ObraContacto::CAMPOS));
            $antes = HistorialDeCampos::valores($contacto, ObraContacto::CAMPOS);
            $contacto->fill($nuevos)->save();

            foreach (HistorialDeCampos::diferencias($antes, HistorialDeCampos::valores($contacto, ObraContacto::CAMPOS)) as $campo => $cambio) {
                $cambios["contacto_{$contacto->tipo->value}.{$campo}"] = $cambio;
            }
        }

        return $cambios;
    }
}
