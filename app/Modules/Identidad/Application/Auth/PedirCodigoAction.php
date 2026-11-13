<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Application\Auth;

use App\Modules\Identidad\Domain\MailNormalizado;
use Illuminate\Contracts\Bus\Dispatcher;

/**
 * F-01 (ADR-015, RN-IDE-02/03): solo un usuario activo recibe el código; la respuesta es siempre
 * la misma (la arma el controller), así no se revela si el mail existe. Pedir otro invalida el anterior.
 *
 * SEG-06: el pedido HTTP hace siempre el mismo trabajo, exista o no el mail: encolar un único
 * EnviarCodigoJob. Buscar al usuario, escribir el código y encolar el mail pasa en el job, así el
 * tiempo de respuesta no delata si la cuenta existe (sin demoras artificiales).
 */
final class PedirCodigoAction
{
    public const RESPUESTA = 'Si el mail está registrado, te llegó un código.';

    public function __construct(private readonly Dispatcher $bus) {}

    public function handle(string $email): void
    {
        $this->bus->dispatch(new EnviarCodigoJob(MailNormalizado::de($email)));
    }
}
