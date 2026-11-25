<?php

declare(strict_types=1);

namespace App\Shared\CargaInicial;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Lo que tira un contrato de carga inicial (`importacion.md` §11.4) cuando el caso de uso del módulo dueño
 * rechaza el dato con sus mismas validaciones (RN-IMP-09): los mensajes para el cliente, sin el campo de la API.
 * Importacion los pone en el informe como faltantes; nada de lo intentado queda guardado.
 */
final class CargaRechazada extends RuntimeException
{
    /**
     * @param  list<string>  $motivos
     */
    public function __construct(public readonly array $motivos)
    {
        parent::__construct(implode(' ', $motivos));
    }

    /**
     * Corre el caso de uso y traduce sus 422/409 a `CargaRechazada`; si estaba en una transacción, ya se deshizo.
     *
     * @template T
     *
     * @param  Closure(): T  $trabajo
     * @return T
     *
     * @throws self
     */
    public static function traducir(Closure $trabajo): mixed
    {
        try {
            return $trabajo();
        } catch (ValidationException $e) {
            throw self::desdeValidacion($e);
        } catch (HttpResponseException $e) {
            throw self::desdeRespuesta($e);
        }
    }

    public static function por(string $motivo): self
    {
        return new self([$motivo]);
    }

    public static function desdeValidacion(ValidationException $e): self
    {
        $motivos = [];

        foreach ($e->errors() as $mensajes) {
            foreach ($mensajes as $mensaje) {
                $motivos[] = $mensaje;
            }
        }

        return new self($motivos === [] ? [$e->getMessage()] : $motivos);
    }

    /** Los 409 y 422 que arman los casos de uso como respuesta (`NumeroUnico`, `ApiResponse::error`). */
    public static function desdeRespuesta(HttpResponseException $e): self
    {
        $cuerpo = json_decode((string) $e->getResponse()->getContent(), true);
        $mensaje = is_array($cuerpo) && is_string($cuerpo['message'] ?? null) ? $cuerpo['message'] : 'No se pudo guardar.';

        return new self([$mensaje]);
    }
}
