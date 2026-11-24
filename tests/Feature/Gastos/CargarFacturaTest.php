<?php

declare(strict_types=1);

namespace Tests\Feature\Gastos;

use App\Modules\Gastos\Domain\Events\GastoImputado;
use App\Modules\Gastos\Domain\Models\Gasto;
use App\Modules\Gastos\Domain\Models\GastoRenglon;
use App\Modules\Obras\Domain\Enums\ObraEstado;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Tests\Feature\Gastos\Support\GastosTestCase;

/** F-39 · F-40 · F-41 · F-42 · HU-061…HU-063 · W-10 (gastos.md RN-GAS-03…16). */
final class CargarFacturaTest extends GastosTestCase
{
    public function test_carga_factura_con_renglones(): void
    {
        Event::fake([GastoImputado::class]);
        $nave = $this->obra('Piso industrial nave 3');
        $losa = $this->obra('Reparación de losa tanque B');

        $respuesta = $this->cargar($this->diego(), $this->facturaDeW10($this->proveedor(), $nave, $losa))
            ->assertCreated()
            ->assertJsonPath('message', 'Factura cargada. Ya se restó en la rendición de Piso industrial nave 3 y Reparación de losa tanque B.')
            ->assertJsonPath('data.numero', '0003-00012345')
            ->assertJsonPath('data.documento', 'Factura 0003-00012345')
            ->assertJsonPath('data.total', '905600.00')
            ->assertJsonPath('data.por_obra.0.total', '549000.00')
            ->assertJsonPath('data.por_obra.1.total', '311600.00')
            ->assertJsonPath('data.por_obra.2', ['obra' => null, 'total' => '45000.00'])
            ->assertJsonPath('data.renglones.0.cantidad', '30.000')
            ->assertJsonPath('data.renglones.0.total', '294000.00')
            ->assertJsonPath('data.renglones.0.fecha', '2026-10-17')
            ->assertJsonPath('data.renglones.4.obra', null)
            ->assertJsonPath('data.obras_afectadas.0.nombre_trabajo', 'Piso industrial nave 3')
            ->assertJsonPath('data.puede', ['editar' => true, 'anular' => true]);

        $id = $respuesta->json('data.id');
        $this->assertIsInt($id);
        $gasto = Gasto::query()->findOrFail($id);
        $this->assertSame(['creado'], $this->acciones($gasto));
        $this->assertSame(5, GastoRenglon::query()->where('gasto_id', $gasto->id)->count());
        Event::assertDispatched(GastoImputado::class, fn (GastoImputado $e): bool => $e->gastoId === $gasto->id && $e->obraIds === [$nave->id, $losa->id]);
    }

    public function test_cargar_dispara_gasto_imputado_con_las_obras_despues_del_commit(): void
    {
        $this->assertContains(ShouldDispatchAfterCommit::class, class_implements(GastoImputado::class) ?: []);
        Event::fake([GastoImputado::class]);
        $nave = $this->obra('Piso industrial nave 3');

        // Si la transacción no se confirma (factura repetida), el evento no sale.
        $datos = $this->facturaDeW10($this->proveedor(), $nave, $nave);
        $this->cargar($this->diego(), $datos)->assertCreated();
        $this->cargar($this->diego(), $datos)->assertUnprocessable();

        Event::assertDispatchedTimes(GastoImputado::class, 1);
    }

    public function test_factura_toda_sin_obra_lo_dice(): void
    {
        $datos = ['origen' => 'factura', 'numero' => '1-1', 'fecha' => '2026-10-17', 'proveedor_id' => $this->proveedor()->id,
            'renglones' => [$this->renglon('Cemento a stock', '10', '9800', null, null)]];

        $this->cargar($this->carla(), $datos)->assertCreated()->assertJsonPath('message', 'Factura cargada. No va a ninguna obra (todo sin obra).');
    }

    public function test_factura_exige_numero_fecha_y_proveedor(): void
    {
        $nave = $this->obra('Piso industrial nave 3');
        $datos = ['origen' => 'factura', 'renglones' => [$this->renglon('Cemento', '1', '10', $nave, 'materiales')]];

        $this->cargar($this->diego(), $datos)->assertUnprocessable()
            ->assertJsonPath('errors.numero.0', 'Poné el número de la factura.')
            ->assertJsonPath('errors.fecha.0', 'Poné la fecha.')
            ->assertJsonPath('errors.proveedor_id.0', 'Elegí el proveedor.');
        $this->assertSame(0, Gasto::query()->count());
    }

    public function test_factura_exige_al_menos_un_renglon(): void
    {
        $datos = ['origen' => 'factura', 'numero' => '1-1', 'fecha' => '2026-10-17', 'proveedor_id' => $this->proveedor()->id, 'renglones' => []];

        $this->cargar($this->diego(), $datos)->assertUnprocessable()->assertJsonPath('errors.renglones.0', 'Cargá al menos un renglón.');
    }

    public function test_renglon_exige_concepto_cantidad_precio_y_obra_o_sin_obra(): void
    {
        $datos = ['origen' => 'factura', 'numero' => '1-1', 'fecha' => '2026-10-17', 'proveedor_id' => $this->proveedor()->id,
            'renglones' => [['concepto' => '', 'cantidad' => '', 'precio_unitario' => '']]];

        $this->cargar($this->diego(), $datos)->assertUnprocessable()
            ->assertJsonPath('errors', fn (array $e): bool => $e['renglones.0.concepto'] === ['Renglón 1: escribí el concepto.']
                && $e['renglones.0.cantidad'][0] === 'Renglón 1: poné la cantidad.'
                && $e['renglones.0.precio_unitario'][0] === 'Renglón 1: poné el precio unitario.'
                && $e['renglones.0.obra_id'][0] === "Renglón 1: elegí la obra o '(sin obra)'.");
    }

    public function test_renglon_con_obra_exige_rubro(): void
    {
        $nave = $this->obra('Piso industrial nave 3');
        $datos = $this->facturaDeW10($this->proveedor(), $nave, $nave);
        $datos['renglones'][1]['rubro'] = null;

        $this->cargar($this->diego(), $datos)->assertUnprocessable()->assertJsonPath('errors', ['renglones.1.rubro' => ['Renglón 2: elegí el rubro.']]);
    }

    public function test_renglon_sin_obra_admite_rubro_vacio(): void
    {
        $nave = $this->obra('Piso industrial nave 3');
        $datos = $this->facturaDeW10($this->proveedor(), $nave, $nave);
        $datos['renglones'][4]['rubro'] = null;

        $this->cargar($this->diego(), $datos)->assertCreated()->assertJsonPath('data.renglones.4.rubro', null);
    }

    public function test_fijos_y_horas_no_se_imputan_en_una_factura(): void
    {
        $nave = $this->obra('Piso industrial nave 3');
        $datos = $this->facturaDeW10($this->proveedor(), $nave, $nave);
        $datos['renglones'][0]['rubro'] = 'horas_liquidadas';

        $this->cargar($this->diego(), $datos)->assertUnprocessable()
            ->assertJsonPath('errors', $this->error('renglones.0.rubro', 'Renglón 1: FIJOS y HORAS LIQUIDADAS se cargan como monto por período, desde la rendición.'));
    }

    public function test_factura_a_obra_terminada_se_acepta(): void
    {
        $losa = $this->obra('Reparación de losa tanque B', estado: ObraEstado::Terminada);

        $this->cargar($this->diego(), $this->facturaDeW10($this->proveedor(), $losa, $losa))->assertCreated()
            ->assertJsonPath('data.renglones.0.obra.estado', 'terminada');
    }

    public function test_factura_a_obra_sin_presupuesto_se_acepta(): void
    {
        $nave = $this->obra('Piso industrial nave 3', estado: ObraEstado::Confirmada);

        $this->cargar($this->diego(), $this->facturaDeW10($this->proveedor(), $nave, $nave))->assertCreated();
    }

    public function test_un_renglon_que_va_a_dos_obras_se_carga_como_dos_renglones(): void
    {
        $nave = $this->obra('Piso industrial nave 3');
        $losa = $this->obra('Reparación de losa tanque B');
        $datos = ['origen' => 'factura', 'numero' => '1-7', 'fecha' => '2026-10-17', 'proveedor_id' => $this->proveedor()->id, 'renglones' => [
            $this->renglon('Cemento x 50 kg', '6', '9800', $nave, 'materiales'),
            $this->renglon('Cemento x 50 kg', '4', '9800', $losa, 'materiales'),
        ]];

        $this->cargar($this->diego(), $datos)->assertCreated()
            ->assertJsonPath('data.por_obra.0.total', '58800.00')
            ->assertJsonPath('data.por_obra.1.total', '39200.00');
    }

    public function test_epp_se_guarda_como_epp(): void
    {
        $nave = $this->obra('Piso industrial nave 3');
        $datos = $this->facturaDeW10($this->proveedor(), $nave, $nave);
        $datos['renglones'][3]['rubro'] = 'epp';

        $this->cargar($this->diego(), $datos)->assertCreated()->assertJsonPath('data.renglones.3.rubro', ['clave' => 'epp', 'nombre' => 'EPP']);
    }

    public function test_fecha_futura_en_argentina_es_rechazada(): void
    {
        // 23:30 del 19-oct en Argentina = 02:30 UTC del 20: "20-oct" es mañana.
        $this->travelTo(Carbon::parse('2026-10-20 02:30:00', 'UTC'));
        $nave = $this->obra('Piso industrial nave 3');

        $this->cargar($this->diego(), $this->facturaDeW10($this->proveedor(), $nave, $nave, ['fecha' => '2026-10-20']))
            ->assertUnprocessable()->assertJsonPath('errors.fecha.0', 'La fecha no puede ser posterior a hoy.');
        $this->cargar($this->diego(), $this->facturaDeW10($this->proveedor(), $nave, $nave, ['fecha' => '2026-10-19']))->assertCreated();
    }

    public function test_backend_ignora_el_total_que_manda_el_front(): void
    {
        $nave = $this->obra('Piso industrial nave 3');
        $datos = $this->facturaDeW10($this->proveedor(), $nave, $nave);
        $datos['renglones'][0]['total'] = '1.00';

        $this->cargar($this->diego(), $datos)->assertCreated()->assertJsonPath('data.renglones.0.total', '294000.00');
    }

    public function test_precio_con_mas_de_dos_decimales_es_rechazado(): void
    {
        $nave = $this->obra('Piso industrial nave 3');
        $datos = $this->facturaDeW10($this->proveedor(), $nave, $nave, ['renglones' => [$this->renglon('Tornillos', '3', '33.335', $nave, 'insumos')]]);

        $this->cargar($this->diego(), $datos)->assertUnprocessable()
            ->assertJsonPath('errors', $this->error('renglones.0.precio_unitario', 'Renglón 1: hasta 2 decimales en el precio.'));

        $datos['renglones'][0]['precio_unitario'] = '33.34';
        $this->cargar($this->diego(), $datos)->assertCreated()->assertJsonPath('data.total', '100.02');
    }

    public function test_cantidad_con_mas_de_tres_decimales_es_rechazada(): void
    {
        $nave = $this->obra('Piso industrial nave 3');
        $datos = $this->facturaDeW10($this->proveedor(), $nave, $nave, ['renglones' => [$this->renglon('Arena', '2.5001', '42500', $nave, 'materiales')]]);

        $this->cargar($this->diego(), $datos)->assertUnprocessable()
            ->assertJsonPath('errors', $this->error('renglones.0.cantidad', 'Renglón 1: hasta 3 decimales en la cantidad.'));
    }

    public function test_cantidad_negativa_solo_en_devolucion(): void
    {
        $nave = $this->obra('Piso industrial nave 3');
        $datos = $this->facturaDeW10($this->proveedor(), $nave, $nave, ['renglones' => [$this->renglon('Cemento', '-4', '9800', $nave, 'materiales')]]);

        $this->cargar($this->diego(), $datos)->assertUnprocessable()
            ->assertJsonPath('errors', $this->error('renglones.0.cantidad', 'Renglón 1: la cantidad va en negativo solo en una devolución.'));

        $datos['renglones'][0]['cantidad'] = '0';
        $this->cargar($this->diego(), $datos)->assertUnprocessable()
            ->assertJsonPath('errors', $this->error('renglones.0.cantidad', 'Renglón 1: la cantidad no puede ser 0.'));
    }

    public function test_total_demasiado_grande_es_rechazado(): void
    {
        $nave = $this->obra('Piso industrial nave 3');
        $datos = $this->facturaDeW10($this->proveedor(), $nave, $nave, ['renglones' => [$this->renglon('Grúa', '1000', '9999999999999.99', $nave, 'subcontratos')]]);

        $this->cargar($this->diego(), $datos)->assertUnprocessable()
            ->assertJsonPath('errors', $this->error('renglones.0.precio_unitario', 'Renglón 1: el total del renglón es demasiado grande.'));
    }

    public function test_hasta_doscientos_renglones(): void
    {
        $nave = $this->obra('Piso industrial nave 3');
        $datos = $this->facturaDeW10($this->proveedor(), $nave, $nave, ['renglones' => array_fill(0, 201, $this->renglon('Tornillo', '1', '10', $nave, 'insumos'))]);

        $this->cargar($this->diego(), $datos)->assertUnprocessable()
            ->assertJsonPath('errors.renglones.0', 'Hasta 200 renglones por comprobante: dividilo en dos cargas.');
    }

    public function test_compra_en_dolares_se_guarda_en_pesos_con_la_cotizacion_en_el_detalle(): void
    {
        $nave = $this->obra('Piso industrial nave 3');
        $datos = $this->facturaDeW10($this->proveedor(), $nave, $nave, ['renglones' => [
            $this->renglon('Disco diamantado importado', '2', '118000', $nave, 'maquinas_herramientas', ['detalle' => 'USD 100 c/u a 1.180']),
        ]]);

        $this->cargar($this->diego(), $datos)->assertCreated()
            ->assertJsonPath('data.renglones.0.detalle', 'USD 100 c/u a 1.180')
            ->assertJsonPath('data.total', '236000.00');
    }

    public function test_factura_rechaza_el_proveedor_generico(): void
    {
        $nave = $this->obra('Piso industrial nave 3');

        $this->cargar($this->diego(), $this->facturaDeW10($this->generico(), $nave, $nave))->assertUnprocessable()
            ->assertJsonPath('errors.proveedor_id.0', 'El proveedor genérico es solo para gastos sin documento.');
    }

    public function test_proveedor_inexistente_devuelve_422(): void
    {
        $nave = $this->obra('Piso industrial nave 3');

        $this->cargar($this->diego(), $this->facturaDeW10($this->proveedor(), $nave, $nave, ['proveedor_id' => 999999]))
            ->assertUnprocessable()->assertJsonPath('errors.proveedor_id.0', 'Elegí el proveedor.');
    }

    public function test_obra_inexistente_devuelve_422_en_su_renglon(): void
    {
        $nave = $this->obra('Piso industrial nave 3');
        $datos = $this->facturaDeW10($this->proveedor(), $nave, $nave);
        $datos['renglones'][2]['obra_id'] = 999999;

        $this->cargar($this->diego(), $datos)->assertUnprocessable()
            ->assertJsonPath('errors', $this->error('renglones.2.obra_id', "Renglón 3: elegí la obra o '(sin obra)'."));
    }

    public function test_renglon_con_material_de_la_obra_o_que_no_esta_en_la_lista(): void
    {
        $nave = $this->obra('Piso industrial nave 3');
        $otra = $this->obra('Impermeabilización cubierta');
        $cemento = $this->material($nave, 'Cemento x 50 kg');
        $ajeno = $this->material($otra, 'Cemento x 50 kg');
        $datos = $this->facturaDeW10($this->proveedor(), $nave, $nave);
        $datos['renglones'][0]['material_id'] = $ajeno->id;

        $this->cargar($this->diego(), $datos)->assertUnprocessable()->assertJsonPath(
            'errors',
            $this->error('renglones.0.material_id', 'Renglón 1: el material Cemento x 50 kg es de otra obra: quitá el material o elegí uno de esta obra.'),
        );

        $datos['renglones'][0]['material_id'] = $cemento->id;
        $datos['renglones'][1]['material_nuevo'] = ['nombre' => 'Arena gruesa', 'unidad' => 'm³'];
        $this->cargar($this->diego(), $datos)->assertCreated()
            ->assertJsonPath('data.renglones.0.material', ['id' => $cemento->id, 'nombre' => 'Cemento x 50 kg', 'unidad' => 'bolsa'])
            ->assertJsonPath('data.renglones.1.material.unidad', 'm³');
        $this->assertSame([['obra' => $nave->id, 'nombre' => 'Arena gruesa', 'unidad' => 'm³']], $this->materiales->creados);
    }

    public function test_sin_gastos_editar_no_carga(): void
    {
        $nave = $this->obra('Piso industrial nave 3');
        $datos = $this->facturaDeW10($this->proveedor(), $nave, $nave);

        $this->cargar($this->marta(), $datos)->assertForbidden()->assertJsonPath('message', 'Solo administración y compras cargan gastos.');
        $this->cargar($this->sofia(), $datos)->assertForbidden();
        $this->assertSame(0, Gasto::query()->count());
    }

    public function test_doble_envio_crea_un_solo_gasto(): void
    {
        $nave = $this->obra('Piso industrial nave 3');
        $diego = $this->diego();
        $datos = $this->facturaDeW10($this->proveedor(), $nave, $nave);
        $clave = ['Idempotency-Key' => '0f8fad5b-d9cb-469f-a165-70867728950e'];

        $primera = $this->cargar($diego, $datos, $clave)->assertCreated();
        $this->cargar($diego, $datos, $clave)->assertCreated()->assertHeader('Idempotent-Replayed', 'true')
            ->assertJsonPath('data.id', $primera->json('data.id'));
        $this->assertSame(1, Gasto::query()->count());
    }

    public function test_no_hay_tipo_nota_de_credito(): void
    {
        $nave = $this->obra('Piso industrial nave 3');

        $this->cargar($this->diego(), $this->facturaDeW10($this->proveedor(), $nave, $nave, ['tipo_documento' => 'nota_credito']))
            ->assertUnprocessable()->assertJsonValidationErrors('tipo_documento');
        $this->cargar($this->diego(), $this->facturaDeW10($this->proveedor(), $nave, $nave, ['origen' => 'nota_credito']))
            ->assertUnprocessable()->assertJsonValidationErrors('origen');
    }
}
