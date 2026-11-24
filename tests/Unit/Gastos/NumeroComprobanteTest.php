<?php

declare(strict_types=1);

namespace Tests\Unit\Gastos;

use App\Modules\Gastos\Domain\Enums\Origen;
use App\Modules\Gastos\Domain\EtiquetaDocumento;
use App\Modules\Gastos\Domain\NumeroComprobante;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/** RN-GAS-07 (resuelta DU-G10), RN-GAS-44 (resuelta DU-G07) y las etiquetas de RN-GAS-29. */
final class NumeroComprobanteTest extends TestCase
{
    public function test_punto_de_venta_y_numero_se_normalizan(): void
    {
        $this->assertSame('0003-00012345', NumeroComprobante::normalizar('3-12345'));
        $this->assertSame('0003-00012345', NumeroComprobante::normalizar(' 0003-00012345 '));
        $this->assertSame('00003-00012345', NumeroComprobante::normalizar('00003 00012345'));
        $this->assertSame('0001-00000456', NumeroComprobante::normalizar('1 - 456'));
    }

    public function test_lo_que_no_tiene_esa_forma_queda_tal_cual_en_mayusculas(): void
    {
        $this->assertSame('A-0003-12345', NumeroComprobante::normalizar('a-0003-12345'));
        $this->assertSame('REMITO 77', NumeroComprobante::normalizar('Remito 77'));
    }

    public function test_para_buscar_ignora_ceros_a_la_izquierda_guiones_y_espacios(): void
    {
        $this->assertSame('312345', NumeroComprobante::paraBuscar('0003-00012345'));
        $this->assertSame('312345', NumeroComprobante::paraBuscar('3-12345'));
        $this->assertSame('312345', NumeroComprobante::paraBuscar('0003 00012345'));
        $this->assertSame('12345', NumeroComprobante::paraBuscar('12345'));
        $this->assertSame(5, NumeroComprobante::digitos('3-1234'));
    }

    public function test_etiquetas_de_documento_iguales_a_la_rendicion(): void
    {
        $desde = new DateTimeImmutable('2026-10-01');
        $hasta = new DateTimeImmutable('2026-10-15');

        $this->assertSame('Factura 0003-00012345', EtiquetaDocumento::de(Origen::Factura, '0003-00012345', null, null));
        $this->assertSame('Sin documento', EtiquetaDocumento::de(Origen::Combustible, null, null, null));
        $this->assertSame('Consumo de stock', EtiquetaDocumento::de(Origen::ConsumoStock, null, null, null));
        $this->assertSame('Retiro · remito 0001-00000456', EtiquetaDocumento::de(Origen::RetiroRemito, '0001-00000456', null, null));
        $this->assertSame('Período 1 al 15-oct', EtiquetaDocumento::de(Origen::MontoPeriodo, null, $desde, $hasta));
        $this->assertSame('Carga inicial', EtiquetaDocumento::de(Origen::SaldoInicial, null, null, null));
        $this->assertSame('20-sep al 5-oct', EtiquetaDocumento::periodo(new DateTimeImmutable('2026-09-20'), new DateTimeImmutable('2026-10-05')));
    }
}
