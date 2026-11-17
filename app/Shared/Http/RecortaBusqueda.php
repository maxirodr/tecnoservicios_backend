<?php

declare(strict_types=1);

namespace App\Shared\Http;

/**
 * Para los FormRequest de buscadores y sugerencias (PU-118): un texto más largo que el máximo se
 * recorta en vez de dar 422. Lo que se escribe en un campo puede ser más largo que lo que se busca, y
 * una sugerencia nunca debe fallar por largo.
 */
trait RecortaBusqueda
{
    protected function recortarBusqueda(string $campo, int $largo): void
    {
        $texto = $this->input($campo);

        if (is_string($texto) && mb_strlen($texto) > $largo) {
            $this->merge([$campo => mb_substr($texto, 0, $largo)]);
        }
    }
}
