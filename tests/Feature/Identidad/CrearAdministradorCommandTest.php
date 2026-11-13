<?php

declare(strict_types=1);

namespace Tests\Feature\Identidad;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Identidad\Infrastructure\Mail\BienvenidaMail;
use Illuminate\Support\Facades\Mail;

/** DU-A10: primer usuario en producción por comando. */
final class CrearAdministradorCommandTest extends IdentidadTestCase
{
    public function test_comando_crea_el_primer_usuario_en_direccion_y_le_manda_el_mail(): void
    {
        Mail::fake();

        $this->command('identidad:crear-administrador', ['email' => ' Eva@Empresa-Ejemplo.com ', 'nombre' => 'Eva'])->assertSuccessful();

        $eva = User::query()->with('sector')->where('email', 'eva@empresa-ejemplo.com')->firstOrFail();
        $this->assertSame('Dirección', $eva->sector->nombre);
        $this->assertTrue($eva->activo);
        $this->assertFalse($eva->eligioContrasena());
        Mail::assertQueued(BienvenidaMail::class, fn (BienvenidaMail $mail): bool => $mail->hasTo('eva@empresa-ejemplo.com'));
    }

    public function test_comando_no_duplica_un_mail_existente(): void
    {
        Mail::fake();
        $this->usuarioDe('Compras', ['email' => 'eva@empresa-ejemplo.com']);

        $this->command('identidad:crear-administrador', ['email' => 'eva@empresa-ejemplo.com', 'nombre' => 'Eva'])->assertFailed();

        Mail::assertNothingQueued();
        $this->assertSame(1, User::query()->where('email', 'eva@empresa-ejemplo.com')->count());
    }
}
