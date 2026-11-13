<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Application\Usuarios;

use App\Modules\Identidad\Contracts\Jerarquia;
use App\Modules\Identidad\Domain\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * RN-IDE-22: "depende de" no puede ser la misma persona ni armar un círculo; al asignarlo, el
 * jefe tiene que estar activo (identidad.md §4.1).
 */
final class ReglasDeJefatura
{
    private const CAMPO = 'jefe_id';

    public const MISMA_PERSONA = 'No puede depender de sí mismo.';

    public const JEFE_INACTIVO = 'La persona elegida está inactiva.';

    public function __construct(private readonly Jerarquia $jerarquia) {}

    /**
     * @param  User|null  $usuario  null en el alta (todavía no tiene gente a cargo)
     */
    public function validar(?User $usuario, int $jefeId): void
    {
        $jefe = User::query()->findOrFail($jefeId);

        if (! $jefe->activo) {
            $this->rechazar(self::JEFE_INACTIVO);
        }

        if ($usuario === null) {
            return;
        }

        if ($jefe->id === $usuario->id) {
            $this->rechazar(self::MISMA_PERSONA);
        }

        if (in_array($jefe->id, $this->jerarquia->subordinados($usuario), true)) {
            $this->rechazar("Así se arma un círculo: {$jefe->nombre} ya depende de {$usuario->nombre}.");
        }
    }

    private function rechazar(string $mensaje): never
    {
        throw ValidationException::withMessages([self::CAMPO => [$mensaje]]);
    }
}
