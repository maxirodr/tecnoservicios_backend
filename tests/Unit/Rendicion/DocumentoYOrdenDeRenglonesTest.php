<?php

declare(strict_types=1);

namespace Tests\Unit\Rendicion;

use App\Modules\Rendicion\Domain\Materiales\ColumnasDeFechas;
use App\Modules\Rendicion\Domain\Renglones\DocumentoDelRenglon;
use App\Modules\Rendicion\Domain\TextoComparable;
use PHPUnit\Framework\TestCase;
use Tests\Feature\Rendicion\Support\Renglon;

/** Etiquetas de documento (RN-REN-29), texto comparable (§4.2) y columnas de fecha de W-48 (RN-REN-50). */
final class DocumentoYOrdenDeRenglonesTest extends TestCase
{
    public function test_etiquetas_de_documento_como_en_gastos_cargados(): void
    {
        $etiquetas = [
            ['factura', ['numero' => '0003-00012345', 'proveedor' => [1, 'Corralón Ejemplo']], 'Factura 0003-00012345'],
            ['sin_comprobante', [], 'Sin documento'],
            ['combustible', [], 'Sin documento'],
            ['consumo_stock', [], 'Consumo de stock'],
            ['amortizacion', [], 'Amortización'],
            ['retiro_remito', ['numero' => '0001-00000077'], 'Retiro · remito 0001-00000077'],
            ['devolucion', [], 'Devolución'],
            ['monto_periodo', ['desde' => '2026-10-01', 'hasta' => '2026-10-15'], 'Período 1 al 15-oct'],
            ['monto_periodo', ['desde' => '2026-10-25', 'hasta' => '2026-11-10'], 'Período 25-oct al 10-nov'],
            ['saldo_inicial', [], 'Carga inicial'],
        ];

        foreach ($etiquetas as [$origen, $extra, $esperada]) {
            $this->assertSame($esperada, DocumentoDelRenglon::etiqueta(Renglon::de(1, 1, '2026-10-15', $origen, 'X', '1.00', 'materiales', $extra)), $origen);
        }

        $this->assertNull(DocumentoDelRenglon::proveedor(Renglon::de(1, 1, '2026-10-15', 'sin_comprobante', 'X', '1.00', 'viaticos')));
    }

    public function test_texto_comparable_ignora_mayusculas_tildes_y_espacios_dobles_y_conserva_la_enie(): void
    {
        $this->assertSame(TextoComparable::de('Cemento x 50 kg'), TextoComparable::de('  cemento  X 50 KG '));
        $this->assertSame('hormigon h-21', TextoComparable::de('Hormigón H-21'));
        $this->assertNotSame(TextoComparable::de('Paño'), TextoComparable::de('Pano'));
    }

    public function test_columnas_de_fecha_seis_y_hasta(): void
    {
        $columnas = ColumnasDeFechas::de(['2026-10-09', '2026-10-01', '2026-10-02', '2026-10-03', '2026-10-04', '2026-10-05', '2026-10-06', '2026-10-07', '2026-10-08', '2026-10-08']);

        $this->assertSame(['2026-10-04', '2026-10-05', '2026-10-06', '2026-10-07', '2026-10-08', '2026-10-09'], $columnas->fechas);
        $this->assertSame('2026-10-03', $columnas->hasta);
        $this->assertTrue($columnas->esAgrupada('2026-10-01'));
        $this->assertFalse($columnas->esAgrupada('2026-10-04'));
        $this->assertNull(ColumnasDeFechas::de(['2026-10-02'])->hasta);
    }
}
