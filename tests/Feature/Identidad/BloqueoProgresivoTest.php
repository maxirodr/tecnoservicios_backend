<?php

declare(strict_types=1);

namespace Tests\Feature\Identidad;

use App\Modules\Identidad\Application\Auth\IngresarAction;
use App\Modules\Identidad\Application\Auth\PedirCodigoAction;
use App\Modules\Identidad\Domain\Models\BloqueoIngreso;
use App\Modules\Identidad\Domain\Models\CodigoIngreso;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Identidad\Infrastructure\CodigosDeIngreso;
use App\Modules\Identidad\Infrastructure\Mail\CodigoIngresoMail;
use Illuminate\Support\Facades\Mail;

/**
 * SEG-01 (ADR-015, enmienda 2026-11-13): bloqueo progresivo del ingreso por código. Quemado = código
 * invalidado por los 5 intentos fallidos. Los códigos se emiten y queman directo con CodigosDeIngreso
 * (sin pasar por el throttle HTTP); lo que ve la persona se prueba por la API.
 */
final class BloqueoProgresivoTest extends IdentidadTestCase
{
    private const MAIL = 'laura@empresa-ejemplo.com';

    private User $laura;

    protected function setUp(): void
    {
        parent::setUp();

        $this->laura = $this->usuarioDe('Compras', ['email' => self::MAIL]);
    }

    public function test_tres_codigos_quemados_seguidos_bloquean_el_mail_una_hora(): void
    {
        $this->quemar(BloqueoIngreso::QUEMADOS_PARA_BLOQUEAR);

        $this->assertFalse($this->recibeCodigo(), 'Bloqueado recién quemado el tercero');
        $this->travel(BloqueoIngreso::HORAS_PRIMER_BLOQUEO * 60 - 1)->minutes();
        $this->assertFalse($this->recibeCodigo(), 'Sigue bloqueado antes de la hora');
        $this->travel(2)->minutes();
        $this->assertTrue($this->recibeCodigo(), 'Cumplida la hora vuelve a recibir el código');
    }

    public function test_dos_codigos_quemados_no_bloquean(): void
    {
        $this->quemar(BloqueoIngreso::QUEMADOS_PARA_BLOQUEAR - 1);

        $this->assertTrue($this->recibeCodigo());
    }

    public function test_reincidencia_tras_el_bloqueo_bloquea_24_horas(): void
    {
        $this->quemar(BloqueoIngreso::QUEMADOS_PARA_BLOQUEAR);
        $this->travel(BloqueoIngreso::HORAS_PRIMER_BLOQUEO * 60 + 1)->minutes();

        $this->quemar(BloqueoIngreso::QUEMADOS_PARA_BLOQUEAR);

        $this->travel(BloqueoIngreso::HORAS_REINCIDENCIA - 1)->hours();
        $this->assertFalse($this->recibeCodigo(), 'A las 23 h sigue bloqueado');
        $this->travel(61)->minutes();
        $this->assertTrue($this->recibeCodigo(), 'Cumplidas las 24 h vuelve a recibir el código');
    }

    public function test_ingreso_exitoso_reinicia_el_contador_de_quemados(): void
    {
        $this->quemar(BloqueoIngreso::QUEMADOS_PARA_BLOQUEAR - 1);
        $this->ingresarBien();

        $this->quemar(BloqueoIngreso::QUEMADOS_PARA_BLOQUEAR - 1);

        $this->assertTrue($this->recibeCodigo(), 'Dos y dos con un ingreso en el medio no son tres seguidos');
    }

    public function test_ingreso_exitoso_despues_de_un_bloqueo_vuelve_al_bloqueo_de_una_hora(): void
    {
        $this->quemar(BloqueoIngreso::QUEMADOS_PARA_BLOQUEAR);
        $this->travel(BloqueoIngreso::HORAS_PRIMER_BLOQUEO * 60 + 1)->minutes();
        $this->ingresarBien();

        $this->quemar(BloqueoIngreso::QUEMADOS_PARA_BLOQUEAR);
        $this->travel(BloqueoIngreso::HORAS_PRIMER_BLOQUEO * 60 + 1)->minutes();

        $this->assertTrue($this->recibeCodigo(), 'Sin reincidencia: 1 hora, no 24');
    }

    public function test_pedir_codigo_durante_el_bloqueo_responde_igual_y_no_manda_mail(): void
    {
        $this->quemar(BloqueoIngreso::QUEMADOS_PARA_BLOQUEAR);
        Mail::fake();

        $bloqueado = $this->desdeElFront()->postJson('/api/auth/pedir-codigo', ['email' => self::MAIL])->assertOk();
        $inexistente = $this->desdeElFront()->postJson('/api/auth/pedir-codigo', ['email' => 'nadie@empresa-ejemplo.com'])->assertOk();

        $this->assertSame($inexistente->json(), $bloqueado->json());
        $bloqueado->assertExactJson(['data' => null, 'message' => PedirCodigoAction::RESPUESTA, 'errors' => null]);
        Mail::assertNothingQueued();
        $this->assertSame(0, CodigoIngreso::query()->count());
    }

    public function test_ingresar_durante_el_bloqueo_responde_el_mismo_422_aunque_el_codigo_sea_correcto(): void
    {
        $this->quemar(BloqueoIngreso::QUEMADOS_PARA_BLOQUEAR);
        $codigo = $this->codigos()->emitir($this->laura);

        $this->desdeElFront()->postJson('/api/auth/ingresar', ['email' => self::MAIL, 'codigo' => $codigo])
            ->assertUnprocessable()
            ->assertJsonPath('errors', ['codigo' => [IngresarAction::CODIGO_INVALIDO]]);
        $this->assertGuest('web');
    }

    /** Emite y quema (5 fallos) la cantidad de códigos pedida. */
    private function quemar(int $cantidad): void
    {
        for ($i = 0; $i < $cantidad; $i++) {
            $codigo = $this->codigos()->emitir($this->laura);
            $otro = $codigo === '000000' ? '111111' : '000000';

            for ($intento = 0; $intento < CodigoIngreso::INTENTOS_MAXIMOS; $intento++) {
                $this->assertFalse($this->codigos()->canjear($this->laura, $otro));
            }
        }
    }

    private function ingresarBien(): void
    {
        $codigo = (string) $this->pedirCodigo(self::MAIL);
        $this->desdeElFront()->postJson('/api/auth/ingresar', ['email' => self::MAIL, 'codigo' => $codigo])->assertOk();
        $this->app['auth']->forgetGuards();
    }

    /** Pide un código por la API (siempre 200) y dice si salió el mail. */
    private function recibeCodigo(): bool
    {
        Mail::fake();
        $this->desdeElFront()->postJson('/api/auth/pedir-codigo', ['email' => self::MAIL])
            ->assertOk()
            ->assertJsonPath('message', PedirCodigoAction::RESPUESTA);

        return Mail::queued(CodigoIngresoMail::class)->isNotEmpty();
    }

    private function codigos(): CodigosDeIngreso
    {
        return $this->app->make(CodigosDeIngreso::class);
    }
}
