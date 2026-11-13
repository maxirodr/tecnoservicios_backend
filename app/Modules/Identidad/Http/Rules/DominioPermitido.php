<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Http\Rules;

use App\Modules\Identidad\Domain\MailNormalizado;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * RN-IDE-26 (resuelta DU-A11): sin restricción de dominio salvo que se configure
 * MAIL_DOMINIOS_PERMITIDOS.
 */
final class DominioPermitido implements ValidationRule
{
    /**
     * @param  list<string>  $dominios
     */
    public function __construct(private readonly array $dominios) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->dominios === [] || ! is_string($value)) {
            return;
        }

        $dominio = substr((string) strrchr(MailNormalizado::de($value), '@'), 1);

        if (! in_array($dominio, $this->dominios, true)) {
            $fail('Ese mail no es de un dominio habilitado para el sistema.');
        }
    }
}
