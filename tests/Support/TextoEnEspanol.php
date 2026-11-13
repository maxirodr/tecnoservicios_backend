<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Support\Arr;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Asegura que lo que le llega al cliente no tenga frases en inglés (las del framework sin traducir:
 * "The :attribute field is required.", "The selected … is invalid.", "Not Found"…).
 */
trait TextoEnEspanol
{
    private const MARCAS_DE_INGLES = '/\b(the|field|must|selected|invalid|required|not found|method|too many|expired|server|please)\b/i';

    /**
     * @param  TestResponse<Response>  $response
     */
    protected function assertRespuestaSinIngles(TestResponse $response): void
    {
        /** @var array<array-key, mixed> $cuerpo */
        $cuerpo = $response->json() ?? [];

        foreach (Arr::dot(Arr::only($cuerpo, ['message', 'errors'])) as $clave => $texto) {
            if (is_string($texto)) {
                $this->assertDoesNotMatchRegularExpression(self::MARCAS_DE_INGLES, $texto, "Texto en inglés en `{$clave}`.");
            }
        }
    }
}
