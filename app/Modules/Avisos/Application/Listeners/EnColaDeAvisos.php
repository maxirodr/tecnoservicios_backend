<?php

declare(strict_types=1);

namespace App\Modules\Avisos\Application\Listeners;

/**
 * Los listeners de avisos corren en la cola `default` con 3 intentos (RN-AVI-01); el `dedupe_key` hace que
 * un reintento no duplique avisos ni mails (RN-AVI-06).
 */
trait EnColaDeAvisos
{
    public string $queue = 'default';

    public int $tries = 3;
}
