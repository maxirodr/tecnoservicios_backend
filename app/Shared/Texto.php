<?php

declare(strict_types=1);

namespace App\Shared;

/** Ayudas de redacción para los mensajes al usuario. */
final class Texto
{
    /**
     * Cierra la frase con un punto, salvo que ya termine en uno: un nombre como "Hormigonera Sur
     * S.R.L." no queda con doble punto (PU-034).
     */
    public static function conPunto(string $frase): string
    {
        $frase = rtrim($frase);

        return str_ends_with($frase, '.') ? $frase : $frase.'.';
    }
}
