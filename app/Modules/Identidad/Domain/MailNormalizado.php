<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Domain;

/**
 * El mail es el usuario de ingreso: se guarda y se compara en minúsculas y sin espacios
 * (identidad.md §4.1, caso borde 1).
 */
final class MailNormalizado
{
    public static function de(string $email): string
    {
        return mb_strtolower(trim($email));
    }
}
