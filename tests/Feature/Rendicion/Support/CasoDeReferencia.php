<?php

declare(strict_types=1);

namespace Tests\Feature\Rendicion\Support;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Obras\Domain\Models\Obra;
use App\Modules\Rendicion\Domain\Models\Material;

/**
 * El caso de referencia de rendicion.md §9 (datos ficticios): obra "Ampliación de nave de ensayo" con su
 * principal por rubro y el A1 solo total, la lista de 4 materiales, la "Obra de control" y los 21 renglones de
 * §9.2 tal como los devolvería Gastos (incluidos el sin obra, el anulado y el de la otra obra).
 *
 * @mixin RendicionTestCase
 */
trait CasoDeReferencia
{
    protected Obra $ampliacion;

    protected Obra $control;

    protected User $responsableAmpliacion;

    protected User $responsableControl;

    /** @var array<string, Material> */
    protected array $materiales = [];

    private const CORRALON = [11, 'Corralón Ejemplo'];

    private const HORMIGONERA = [12, 'Hormigonera Ejemplo'];

    private const FERRETERIA = [13, 'Ferretería Ejemplo'];

    private const TECNOBAHIA = [14, 'Tecnobahía'];

    private const TALLER = [15, 'Taller Ejemplo'];

    private const SUBCONTRATISTA = [16, 'Subcontratista Ejemplo'];

    /** Gasto de la factura 0004-00000020 (renglones 5 y 6), el que se anula en §9.4. */
    protected const FACTURA_HORMIGONERA = 301;

    protected function cargarCasoDeReferencia(): void
    {
        $this->responsableAmpliacion = $this->laura();
        $this->responsableControl = $this->hernan();
        $this->ampliacion = $this->obraDe($this->responsableAmpliacion, numero: '1-200-2026');
        $this->ampliacion->update(['nombre_trabajo' => 'Ampliación de nave de ensayo', 'cliente' => 'Planta Ejemplo SA']);
        $principal = $this->principal($this->ampliacion);
        $this->porRubro($principal, [
            'materiales' => '5000000.00', 'insumos' => '800000.00', 'subcontratos' => '1200000.00', 'logistica' => '300000.00',
            'amortizacion' => '200000.00', 'obradores_banos' => '150000.00', 'maquinas_herramientas' => '400000.00',
            'viaticos' => '600000.00', 'fijos' => '500000.00', 'horas_liquidadas' => '2000000.00',
        ], '15000000.00');
        $this->soloTotal($this->adicional($this->ampliacion, '1-200-2026 A1'), '1000000.00', '1300000.00');

        $this->materiales = [
            'cemento' => $this->material($this->ampliacion, 'Cemento x 50 kg', 'bolsa', '150', '9800', $principal),
            'hormigon' => $this->material($this->ampliacion, 'Hormigón H-21', 'm³', '40', '100000', $principal),
            'endurecedor' => $this->material($this->ampliacion, 'Endurecedor superficial', 'bolsa', '50', '18000', $principal),
            'malla' => $this->material($this->ampliacion, 'Malla sima', 'paño', '30', '21000', $principal),
            'guantes' => $this->material($this->ampliacion, 'Guantes de trabajo', 'par', null),
        ];

        $this->control = $this->obraDe($this->responsableControl, numero: '2-300-2026');
        $this->control->update(['nombre_trabajo' => 'Obra de control', 'cliente' => 'Cliente Control SA']);
        $this->porRubro($this->principal($this->control), ['subcontratos' => '250000.00', 'materiales' => '750000.00']);

        $this->cargarRenglones();
    }

    private function cargarRenglones(): void
    {
        $a = $this->ampliacion->id;
        $m = fn (string $clave): int => $this->materiales[$clave]->id;
        $f101 = ['proveedor' => self::CORRALON, 'numero' => '0001-00000101'];
        $f020 = ['proveedor' => self::HORMIGONERA, 'numero' => '0004-00000020'];
        $f045 = ['proveedor' => self::SUBCONTRATISTA, 'numero' => '0007-00000045'];

        $this->gastado->agregar($a, Renglon::de(901, 300, '2026-10-02', 'factura', 'Cemento x 50 kg', '985050.00', 'materiales', [...$f101, 'cantidad' => '100.000', 'precio' => '9850.50', 'material' => $m('cemento')]));
        $this->gastado->agregar($a, Renglon::de(902, 300, '2026-10-02', 'factura', 'Guantes de trabajo (par)', '62000.00', 'epp', [...$f101, 'cantidad' => '20.000', 'precio' => '3100.00', 'material' => $m('guantes')]));
        $this->gastado->agregar($a, Renglon::de(903, 300, '2026-10-02', 'factura', 'Disco de corte 230 mm', '43333.30', 'insumos', [...$f101, 'cantidad' => '10.000', 'precio' => '4333.33']));
        $this->gastado->agregar(null, Renglon::de(904, 300, '2026-10-02', 'factura', 'Flete', '45000.00', 'logistica', $f101));
        $this->gastado->agregar($a, Renglon::de(905, self::FACTURA_HORMIGONERA, '2026-10-08', 'factura', 'Hormigón H-21 (m³)', '1265432.13', 'materiales', [...$f020, 'cantidad' => '12.500', 'precio' => '101234.57', 'material' => $m('hormigon')]));
        $this->gastado->agregar($a, Renglon::de(906, self::FACTURA_HORMIGONERA, '2026-10-08', 'factura', 'Flete hormigón', '260000.00', 'logistica', $f020));
        $this->gastado->agregar($a, Renglon::de(907, 302, '2026-10-10', 'factura', 'Tornillos autoperforantes', '6000.00', 'insumos', ['proveedor' => self::FERRETERIA, 'numero' => '0002-00000555', 'cantidad' => '500.000', 'precio' => '12.00']), anulado: true);
        $this->gastado->agregar($a, Renglon::de(908, 303, '2026-10-12', 'devolucion', 'Cemento x 50 kg (devuelto al corralón)', '-49252.50', 'materiales', ['proveedor' => self::CORRALON, 'cantidad' => '-5.000', 'precio' => '9850.50']));
        $this->gastado->agregar($a, Renglon::de(909, 304, '2026-10-14', 'retiro_remito', 'Endurecedor superficial (bolsa)', '540000.00', 'materiales', ['proveedor' => self::TECNOBAHIA, 'numero' => '0001-00000077', 'cantidad' => '30.000', 'precio' => '18000.00', 'material' => $m('endurecedor')]));
        $this->gastado->agregar($a, Renglon::de(910, 305, '2026-10-15', 'sin_comprobante', 'Comida cuadrilla (6 personas)', '42000.00', 'viaticos', ['cantidad' => '6.000', 'precio' => '7000.00']));
        $this->gastado->agregar($a, Renglon::de(911, 306, '2026-10-15', 'combustible', 'Combustible camioneta', '68000.00', 'logistica'));
        $this->gastado->agregar($a, Renglon::de(912, 307, '2026-10-16', 'consumo_stock', 'Antiparras', '25000.00', 'epp', ['cantidad' => '10.000', 'precio' => '2500.00']));
        $this->gastado->agregar($a, Renglon::de(913, 308, '2026-10-18', 'factura', 'Repuesto bomba de agua', '35000.00', 'mantenimiento', ['proveedor' => self::TALLER, 'numero' => '0003-00000300']));
        $this->gastado->agregar($a, Renglon::de(914, 309, '2026-10-19', 'factura', 'Adelanto 50 % demolición', '700000.00', 'subcontratos', $f045));
        $this->gastado->agregar($this->control->id, Renglon::de(915, 309, '2026-10-19', 'factura', 'Andamio', '300000.00', 'subcontratos', $f045));
        $this->gastado->agregar($a, Renglon::de(916, 310, '2026-10-20', 'devolucion', 'Cemento x 50 kg (sobrante)', '-39402.00', 'materiales', ['cantidad' => '-4.000', 'precio' => '9850.50', 'material' => $m('cemento')]));
        $this->gastado->agregar($a, Renglon::de(917, 311, '2026-10-31', 'amortizacion', 'Amortización de herramientas', '190000.00', 'amortizacion'));
        $this->gastado->agregar($a, Renglon::de(918, 312, '2026-10-15', 'monto_periodo', 'Horas liquidadas 1 al 15-oct', '950000.00', 'horas_liquidadas', ['desde' => '2026-10-01', 'hasta' => '2026-10-15']));
        $this->gastado->agregar($a, Renglon::de(919, 313, '2026-10-31', 'monto_periodo', 'Horas liquidadas 16 al 31-oct', '1130000.75', 'horas_liquidadas', ['desde' => '2026-10-16', 'hasta' => '2026-10-31']));
        $this->gastado->agregar($a, Renglon::de(920, 314, '2026-10-31', 'monto_periodo', 'Fijos 1 al 31-oct', '260000.00', 'fijos', ['desde' => '2026-10-01', 'hasta' => '2026-10-31']));
        $this->gastado->agregar($a, Renglon::de(921, 315, '2026-11-30', 'monto_periodo', 'Fijos 1 al 30-nov', '260000.00', 'fijos', ['desde' => '2026-11-01', 'hasta' => '2026-11-30']));
    }
}
