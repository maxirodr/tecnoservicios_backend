<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Shared\Actividad\RegistroActividad;
use Illuminate\Database\Eloquent\Model;

/**
 * Espía de RegistroActividad mientras la implementación real (tabla `actividades`) vive en la rama
 * transversal. Tras el merge, los tests que lo usan pasan a asertar contra la tabla.
 */
final class RegistroActividadEspia implements RegistroActividad
{
    /** @var list<array{sujeto: Model, accion: string, cambios: array<string, array{antes: mixed, despues: mixed}>}> */
    public array $registros = [];

    public function registrar(Model $sujeto, string $accion, array $cambios = []): void
    {
        $this->registros[] = ['sujeto' => $sujeto, 'accion' => $accion, 'cambios' => $cambios];
    }

    /**
     * @param  class-string<Model>  $tipo
     * @return list<array{sujeto: Model, accion: string, cambios: array<string, array{antes: mixed, despues: mixed}>}>
     */
    public function de(string $tipo, string $accion): array
    {
        return array_values(array_filter(
            $this->registros,
            static fn (array $registro): bool => $registro['sujeto'] instanceof $tipo && $registro['accion'] === $accion,
        ));
    }
}
