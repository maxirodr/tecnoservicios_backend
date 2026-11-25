<?php

declare(strict_types=1);

namespace Tests\Feature\Integracion\Support;

use App\Modules\Identidad\Domain\Models\User;
use Tests\Feature\Shared\Adjuntos\Support\ArchivosDePrueba;

/**
 * El caso de referencia de rendicion.md §9 cargado **solo por la API**, como lo haría la gente: las obras, los
 * presupuestos y sus montos por la de Obras (W-06, W-22, W-13), la lista de materiales por la de Rendición (W-48) y
 * los 21 renglones de §9.2 por la de Gastos (W-10, W-35, W-46). Sin fakes: Gastos y Rendición reales.
 *
 * @mixin IntegracionTestCase
 */
trait CasoDeReferenciaPorApi
{
    protected int $ampliacion;

    protected int $control;

    /** La factura 0004-00000020 de la Hormigonera (renglones 5 y 6), la que se anula en §9.4. */
    protected int $facturaHormigonera;

    /** @var array<string, int> */
    private array $proveedores = [];

    protected function cargarCasoDeReferenciaPorApi(User $diego, User $laura, User $hernan): void
    {
        $this->ampliacion = $this->obraPorApi($diego, $laura, 'Ampliación de nave de ensayo', 'Planta Ejemplo SA', '1-200-2026');
        $principal = $this->principalDe($diego, $this->ampliacion);
        $this->montosPorApi($diego, $principal, ['modo' => 'por_rubro', 'montos' => [
            'materiales' => '5000000.00', 'insumos' => '800000.00', 'subcontratos' => '1200000.00', 'logistica' => '300000.00',
            'amortizacion' => '200000.00', 'mantenimiento' => null, 'obradores_banos' => '150000.00', 'servicios_generales' => null,
            'maquinas_herramientas' => '400000.00', 'viaticos' => '600000.00', 'fijos' => '500000.00', 'horas_liquidadas' => '2000000.00',
        ]], '15000000.00');
        $adicional = $this->actingAs($diego)->postJson("/api/obras/{$this->ampliacion}/presupuestos", ['numero' => '1-200-2026 A1'])->assertCreated()->json('data.id');
        $this->assertIsInt($adicional);
        $this->montosPorApi($diego, $adicional, ['modo' => 'solo_total', 'total' => '1000000.00'], '1300000.00');

        $material = fn (string $nombre, string $unidad, string $cantidad, string $precio): int => $this->idDe($this->actingAs($diego)->postJson(
            "/api/obras/{$this->ampliacion}/materiales",
            ['nombre' => $nombre, 'unidad' => $unidad, 'cantidad_presupuestada' => $cantidad, 'precio_presupuestado' => $precio, 'presupuesto_id' => $principal],
        )->assertCreated()->json('data.id'));
        $cemento = $material('Cemento x 50 kg', 'bolsa', '150', '9800.00');
        $hormigon = $material('Hormigón H-21', 'm³', '40', '100000.00');
        $endurecedor = $material('Endurecedor superficial', 'bolsa', '50', '18000.00');
        $material('Malla sima', 'paño', '30', '21000.00');

        $this->control = $this->obraPorApi($diego, $hernan, 'Obra de control', 'Cliente Control SA', '2-300-2026');
        $this->montosPorApi($diego, $this->principalDe($diego, $this->control), ['modo' => 'por_rubro', 'montos' => ['subcontratos' => '250000.00', 'materiales' => '750000.00']]);

        $this->cargarRenglonesPorApi($diego, $cemento, $hormigon, $endurecedor);
    }

    private function cargarRenglonesPorApi(User $diego, int $cemento, int $hormigon, int $endurecedor): void
    {
        $a = $this->ampliacion;
        $this->gastoPorApi($diego, ['origen' => 'factura', 'numero' => '1-101', 'fecha' => '2026-10-02', 'proveedor_id' => $this->proveedorPorApi($diego, 'Corralón Ejemplo'), 'renglones' => [
            $this->renglon('Cemento x 50 kg', '100', '9850.50', $a, 'materiales', ['material_id' => $cemento]),
            $this->renglon('Guantes de trabajo (par)', '20', '3100.00', $a, 'epp', ['material_nuevo' => ['nombre' => 'Guantes de trabajo', 'unidad' => 'par']]),
            $this->renglon('Disco de corte 230 mm', '10', '4333.33', $a, 'insumos'),
            $this->renglon('Flete', '1', '45000.00', null, 'logistica'),
        ]]);
        $this->facturaHormigonera = $this->gastoPorApi($diego, ['origen' => 'factura', 'numero' => '4-20', 'fecha' => '2026-10-08', 'proveedor_id' => $this->proveedorPorApi($diego, 'Hormigonera Ejemplo'), 'renglones' => [
            $this->renglon('Hormigón H-21 (m³)', '12.5', '101234.57', $a, 'materiales', ['material_id' => $hormigon]),
            $this->renglon('Flete hormigón', '1', '260000.00', $a, 'logistica'),
        ]]);
        $tornillos = $this->gastoPorApi($diego, ['origen' => 'factura', 'numero' => '2-555', 'fecha' => '2026-10-10', 'proveedor_id' => $this->proveedorPorApi($diego, 'Ferretería Ejemplo'),
            'renglones' => [$this->renglon('Tornillos autoperforantes', '500', '12.00', $a, 'insumos')]]);
        $this->actingAs($diego)->postJson("/api/gastos/{$tornillos}/anular", ['motivo' => 'Caso de referencia: anulada'])->assertOk();
        $this->gastoPorApi($diego, ['origen' => 'devolucion', 'fecha' => '2026-10-12', 'proveedor_id' => $this->proveedorPorApi($diego, 'Corralón Ejemplo'),
            'renglones' => [$this->renglon('Cemento x 50 kg (devuelto al corralón)', '-5', '9850.50', $a, 'materiales')]]);
        $this->gastoPorApi($diego, ['origen' => 'retiro_remito', 'numero' => '1-77', 'fecha' => '2026-10-14', 'proveedor_id' => $this->proveedorPorApi($diego, 'Tecnobahía'),
            'renglones' => [$this->renglon('Endurecedor superficial (bolsa)', '30', '18000.00', $a, 'materiales', ['material_id' => $endurecedor])]]);
        $this->gastoPorApi($diego, ['origen' => 'sin_comprobante', 'fecha' => '2026-10-15', 'rendido_por' => 'supervisor de obra', 'renglones' => [
            $this->renglon('Comida cuadrilla (6 personas)', '6', '7000.00', $a, 'viaticos'),
            $this->renglon('Combustible camioneta', '1', '68000.00', $a, 'logistica'),
        ]]);
        $this->gastoPorApi($diego, ['origen' => 'consumo_stock', 'fecha' => '2026-10-16', 'renglones' => [$this->renglon('Antiparras', '10', '2500.00', $a, 'epp')]]);
        $this->gastoPorApi($diego, ['origen' => 'factura', 'numero' => '3-300', 'fecha' => '2026-10-18', 'proveedor_id' => $this->proveedorPorApi($diego, 'Taller Ejemplo'),
            'renglones' => [$this->renglon('Repuesto bomba de agua', '1', '35000.00', $a, 'mantenimiento')]]);
        $this->gastoPorApi($diego, ['origen' => 'factura', 'numero' => '7-45', 'fecha' => '2026-10-19', 'proveedor_id' => $this->proveedorPorApi($diego, 'Subcontratista Ejemplo'), 'renglones' => [
            $this->renglon('Adelanto 50 % demolición', '1', '700000.00', $a, 'subcontratos'),
            $this->renglon('Andamio', '1', '300000.00', $this->control, 'subcontratos'),
        ]]);
        $this->gastoPorApi($diego, ['origen' => 'devolucion', 'fecha' => '2026-10-20',
            'renglones' => [$this->renglon('Cemento x 50 kg (sobrante)', '-4', '9850.50', $a, 'materiales', ['material_id' => $cemento])]]);
        $this->gastoPorApi($diego, ['origen' => 'amortizacion', 'fecha' => '2026-10-31',
            'renglones' => [$this->renglon('Amortización de herramientas', '1', '190000.00', $a, 'amortizacion')]]);

        foreach ([
            ['2026-10-01', '2026-10-15', 'Horas liquidadas 1 al 15-oct', '950000.00', 'horas_liquidadas'],
            ['2026-10-16', '2026-10-31', 'Horas liquidadas 16 al 31-oct', '1130000.75', 'horas_liquidadas'],
            ['2026-10-01', '2026-10-31', 'Fijos 1 al 31-oct', '260000.00', 'fijos'],
            ['2026-11-01', '2026-11-30', 'Fijos 1 al 30-nov', '260000.00', 'fijos'],
        ] as [$desde, $hasta, $concepto, $importe, $rubro]) {
            $this->gastoPorApi($diego, ['origen' => 'monto_periodo', 'periodo_desde' => $desde, 'periodo_hasta' => $hasta,
                'renglones' => [$this->renglon($concepto, '1', $importe, $a, $rubro)]]);
        }
    }

    /** W-06 + W-22: alta con su oferta y paso a En ejecución (la rendición en curso solo lista esas). */
    private function obraPorApi(User $diego, User $responsable, string $trabajo, string $cliente, string $numero): int
    {
        $oferta = $this->idDe($this->actingAs($diego)
            ->post('/api/adjuntos', ['contexto' => 'presupuesto', 'archivo' => ArchivosDePrueba::pdf("oferta-{$numero}.pdf")], ['Accept' => 'application/json'])
            ->assertCreated()->json('data.id'));
        $contacto = static fn (string $tipo): array => ['nombre' => "Contacto de {$tipo} (ejemplo)", 'email' => "{$tipo}@planta-ejemplo.com", 'telefono' => '291 000-0000'];
        $obra = $this->idDe($this->actingAs($diego)->postJson('/api/obras', [
            'nombre_trabajo' => $trabajo, 'cliente' => $cliente, 'planta' => null, 'responsable_id' => $responsable->id,
            'fecha_inicio_prevista' => null, 'fecha_fin_prevista' => null, 'carpeta_drive_url' => null,
            'presupuesto' => ['numero' => $numero, 'revision' => 'REV01', 'oferta' => $oferta], 'adicionales' => [],
            'contactos' => ['solicitante' => $contacto('mantenimiento'), 'comprador' => $contacto('compras')],
        ])->assertCreated()->json('data.id'));
        $this->actingAs($diego)->postJson("/api/obras/{$obra}/estado", ['estado' => 'en_ejecucion'])->assertOk();

        return $obra;
    }

    private function principalDe(User $diego, int $obra): int
    {
        $presupuestos = $this->actingAs($diego)->getJson("/api/obras/{$obra}/presupuesto-por-rubro")->assertOk()->json('data.presupuestos');
        $this->assertIsArray($presupuestos);

        return $this->idDe($presupuestos[0]['id'] ?? null);
    }

    /** @param  array<string, mixed>  $montos */
    private function montosPorApi(User $diego, int $presupuesto, array $montos, ?string $montoObra = null): void
    {
        $this->actingAs($diego)->putJson("/api/presupuestos/{$presupuesto}/montos", $montos)->assertOk();

        if ($montoObra !== null) {
            $this->actingAs($diego)->putJson("/api/presupuestos/{$presupuesto}/monto-obra", ['monto_obra' => $montoObra])->assertOk();
        }
    }

    private function proveedorPorApi(User $diego, string $nombre): int
    {
        return $this->proveedores[$nombre] ??= $this->idDe($this->actingAs($diego)->postJson('/api/proveedores', ['nombre' => $nombre])->assertCreated()->json('data.id'));
    }

    /** @param  array<string, mixed>  $datos */
    private function gastoPorApi(User $diego, array $datos): int
    {
        return $this->idDe($this->actingAs($diego)->postJson('/api/gastos', $datos)->assertCreated()->json('data.id'));
    }

    /**
     * @param  array<string, mixed>  $cambios
     * @return array<string, mixed>
     */
    private function renglon(string $concepto, string $cantidad, string $precio, ?int $obra, string $rubro, array $cambios = []): array
    {
        return ['concepto' => $concepto, 'cantidad' => $cantidad, 'precio_unitario' => $precio, 'obra_id' => $obra, 'rubro' => $rubro, ...$cambios];
    }

    private function idDe(mixed $id): int
    {
        $this->assertIsInt($id);

        return $id;
    }
}
