<?php

declare(strict_types=1);

namespace App\Shared\Actividad;

use Illuminate\Database\Eloquent\Model;

/**
 * Quién hizo qué (ADR-010, F-14): se llama desde el caso de uso, dentro de su transacción,
 * con el nombre de negocio de la acción. `$cambios` = {campo: {antes, despues}}.
 */
interface RegistroActividad
{
    /**
     * @param  array<string, array{antes: mixed, despues: mixed}>  $cambios
     */
    public function registrar(Model $sujeto, string $accion, array $cambios = []): void;
}
