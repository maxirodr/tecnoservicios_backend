<?php

declare(strict_types=1);

namespace Tests\Feature\Shared;

use App\Shared\Mail\QueuedMailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

final class QueuedMailableTest extends TestCase
{
    #[Test]
    public function system_mail_is_always_queued_on_the_mail_queue(): void
    {
        Mail::fake();

        Mail::to('persona@example.com')->send(new FakeAvisoMail);

        Mail::assertQueued(FakeAvisoMail::class, fn (FakeAvisoMail $mail): bool => $mail->queue === 'mail');
        Mail::assertNothingSent();
    }

    #[Test]
    public function mail_goes_out_from_noreply_with_configurable_reply_to(): void
    {
        config([
            'mail.from' => ['address' => 'noreply@example.com', 'name' => 'Sistema'],
            'mail.reply_to' => ['address' => 'administracion@example.com', 'name' => 'Administración'],
        ]);
        app('mail.manager')->forgetMailers();

        Mail::to('persona@example.com')->sendNow(new FakeAvisoMail);

        /** @var ArrayTransport $transport */
        $transport = app('mail.manager')->mailer()->getSymfonyTransport();
        $message = $transport->messages()->first()?->getOriginalMessage();

        $this->assertInstanceOf(Email::class, $message);
        $this->assertSame('noreply@example.com', $message->getFrom()[0]->getAddress());
        $this->assertSame('administracion@example.com', $message->getReplyTo()[0]->getAddress());
    }
}

final class FakeAvisoMail extends QueuedMailable
{
    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Aviso de prueba');
    }

    public function content(): Content
    {
        return new Content(htmlString: '<p>Aviso de prueba</p>');
    }
}
