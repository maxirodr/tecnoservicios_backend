<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Infrastructure;

use App\Modules\Identidad\Domain\Models\CodigoIngreso;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Identidad\Infrastructure\Mail\CodigoIngresoMail;
use Illuminate\Support\Facades\Mail;

/**
 * Mails de cuenta (RN-IDE-08): no son "avisos", salen desde la semana 1, por la cola `mail`.
 * Hoy el único es el código para entrar (ADR-015).
 */
final class MailsDeCuenta
{
    public function enviarCodigo(User $user, string $codigo): void
    {
        Mail::to($user->email)->queue(new CodigoIngresoMail($user->nombre, $codigo, CodigoIngreso::VIGENCIA_MINUTOS));
    }
}
