<?php

declare(strict_types=1);

namespace Tests\Unit\Obras;

use App\Modules\Obras\Domain\Enums\ObraEstado;
use App\Modules\Obras\Domain\Estados\ObraStateMachine;
use App\Modules\Obras\Domain\Estados\TransicionNoPermitida;
use App\Modules\Obras\Domain\Models\Obra;
use App\Modules\Obras\Domain\Valores\Revision;
use App\Modules\Obras\Domain\Valores\ValorInvalido;
use App\Shared\Domain\Importe;
use DateTimeImmutable;
use Tests\TestCase;

/** RN-OBR-14 (revisión), RN-OBR-29 (transiciones) y ADR-008 (importes). Sin base: la app solo da los casts. */
final class RevisionYEstadoTest extends TestCase
{
    public function test_normaliza_variantes_a_rev_dos_digitos(): void
    {
        foreach (['REV01', 'rev01', 'Rev 1', 'REV1', 'REV-1', 'R1', '1'] as $escrita) {
            $this->assertSame('REV01', Revision::desde($escrita)->valor, $escrita);
        }

        $this->assertSame('REV02', Revision::desde('rev 2')->valor);
    }

    public function test_rechaza_revision_con_otro_texto(): void
    {
        $this->expectException(ValorInvalido::class);
        $this->expectExceptionMessage('Escribí la revisión como REV01.');

        Revision::desde('versión final');
    }

    public function test_se_avanza_y_se_vuelve_de_a_un_paso(): void
    {
        $this->assertTrue(ObraEstado::Confirmada->puedePasarA(ObraEstado::EnEjecucion));
        $this->assertTrue(ObraEstado::EnEjecucion->puedePasarA(ObraEstado::Terminada));
        $this->assertTrue(ObraEstado::Terminada->puedePasarA(ObraEstado::EnEjecucion));
        $this->assertTrue(ObraEstado::EnEjecucion->puedePasarA(ObraEstado::Confirmada));
        $this->assertFalse(ObraEstado::Confirmada->puedePasarA(ObraEstado::Terminada));
        $this->assertFalse(ObraEstado::Terminada->puedePasarA(ObraEstado::Confirmada));
    }

    public function test_terminar_guarda_quien_y_cuando_y_volver_lo_limpia(): void
    {
        $obra = new Obra;
        $obra->estado = ObraEstado::EnEjecucion;
        $maquina = new ObraStateMachine;

        $maquina->transicionar($obra, ObraEstado::Terminada, 7, new DateTimeImmutable('2026-11-13 10:00'));
        $this->assertSame(7, $obra->terminada_por);
        $this->assertNotNull($obra->terminada_at);

        $maquina->transicionar($obra, ObraEstado::EnEjecucion, 7, new DateTimeImmutable('2026-11-14 10:00'));
        $this->assertNull($obra->terminada_por);
        $this->assertNull($obra->terminada_at);
    }

    public function test_saltar_de_confirmada_a_terminada_no_se_permite(): void
    {
        $obra = new Obra;
        $obra->estado = ObraEstado::Confirmada;

        $this->expectException(TransicionNoPermitida::class);
        $this->expectExceptionMessage('De Confirmada se pasa primero a En ejecución.');

        (new ObraStateMachine)->transicionar($obra, ObraEstado::Terminada, 1, new DateTimeImmutable);
    }

    public function test_importe_suma_sin_float_y_valida_decimales(): void
    {
        $this->assertSame('12400000.50', Importe::desde('10900000.25')->sumar(Importe::desde('1500000.25'))->valor);
        $this->assertTrue(Importe::desde('-150000')->esNegativo());
        $this->assertSame(3, Importe::decimales('1.005'));
    }
}
