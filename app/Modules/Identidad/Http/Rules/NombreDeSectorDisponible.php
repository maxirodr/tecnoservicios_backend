<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Http\Rules;

use App\Modules\Identidad\Domain\Models\Sector;
use App\Modules\Identidad\Domain\NombreClave;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Nombre de sector único entre los activos, sin distinguir mayúsculas, tildes ni espacios
 * (identidad.md §4.2 y §7, caso borde 11). La base lo garantiza además con un índice único.
 */
final class NombreDeSectorDisponible implements ValidationRule
{
    public function __construct(private readonly ?int $ignorarSectorId = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $existe = Sector::query()
            ->where('activo', true)
            ->where('nombre_clave', NombreClave::de($value))
            ->when($this->ignorarSectorId !== null, fn ($query) => $query->whereKeyNot($this->ignorarSectorId))
            ->exists();

        if ($existe) {
            $fail('Ya hay un sector con ese nombre.');
        }
    }
}
