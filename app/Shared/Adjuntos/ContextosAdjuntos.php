<?php

declare(strict_types=1);

namespace App\Shared\Adjuntos;

use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

/**
 * Registro de contextos de adjuntos (`presupuesto`, `documento_obra`, `pedido`…) → quién autoriza.
 * Cada módulo registra los suyos en el boot() de su provider. Un contexto no registrado no existe:
 * `POST /api/adjuntos` lo rechaza con 422.
 */
final class ContextosAdjuntos
{
    private const LARGO_MAXIMO = 30;

    /** @var array<string, class-string<AutorizaAdjuntos>|AutorizaAdjuntos> */
    private array $autorizadores = [];

    public function __construct(private readonly Container $container) {}

    /**
     * @param  class-string<AutorizaAdjuntos>|AutorizaAdjuntos  $autorizador
     */
    public function registrar(string $contexto, string|AutorizaAdjuntos $autorizador): void
    {
        if (preg_match('/^[a-z_]{1,'.self::LARGO_MAXIMO.'}$/', $contexto) !== 1) {
            throw new InvalidArgumentException("Contexto de adjunto inválido: {$contexto}");
        }

        $this->autorizadores[$contexto] = $autorizador;
    }

    public function existe(string $contexto): bool
    {
        return isset($this->autorizadores[$contexto]);
    }

    /**
     * @return list<string>
     */
    public function contextos(): array
    {
        return array_keys($this->autorizadores);
    }

    public function autorizador(string $contexto): ?AutorizaAdjuntos
    {
        $autorizador = $this->autorizadores[$contexto] ?? null;

        if (is_string($autorizador)) {
            $autorizador = $this->container->make($autorizador);
            $this->autorizadores[$contexto] = $autorizador;
        }

        return $autorizador;
    }
}
