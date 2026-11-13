<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Http\Rules;

use App\Modules\Identidad\Domain\MailNormalizado;
use App\Modules\Identidad\Domain\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * El mail es único entre todos los usuarios, activos e inactivos (identidad.md §4.1, caso borde 4).
 */
final class MailDisponible implements ValidationRule
{
    public function __construct(private readonly ?int $ignorarUsuarioId = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $otro = User::query()
            ->where('email', MailNormalizado::de($value))
            ->when($this->ignorarUsuarioId !== null, fn ($query) => $query->whereKeyNot($this->ignorarUsuarioId))
            ->first();

        if ($otro === null) {
            return;
        }

        $fail($otro->activo
            ? "Ese mail ya es de otro usuario ({$otro->nombre})."
            : "Ese mail ya es de otro usuario ({$otro->nombre}, inactivo). Podés reactivarlo.");
    }
}
