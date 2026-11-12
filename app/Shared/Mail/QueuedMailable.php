<?php

declare(strict_types=1);

namespace App\Shared\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Base de todo mail del sistema: siempre por la cola `mail` (worker a 1 mail/s, ver config/supervisor.php).
 * From (noreply) y reply-to salen de config/mail.php (MAIL_FROM_*, MAIL_REPLY_TO_*), aplicados globalmente.
 */
abstract class QueuedMailable extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct()
    {
        $this->onQueue('mail');
    }
}
