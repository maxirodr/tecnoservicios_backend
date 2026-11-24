<?php

declare(strict_types=1);

namespace Tests\Feature\Gastos;

use App\Modules\Gastos\Contracts\GastadoDeObras;
use App\Modules\Obras\Domain\Models\Obra;
use Tests\Feature\Gastos\Support\GastosTestCase;

/** F-44 · F-47 · HU-066 · W-35: gastos de obra rendidos, combustible y consumo de stock (RN-GAS-17…21). */
final class SinComprobanteTest extends GastosTestCase
{
    /**
     * §10 caso 19: Laura rinde combustible 68.000 (15-oct), comida 42.000 (16-oct), peaje 3.500 (16-oct).
     *
     * @param  array<string, mixed>  $cambios
     * @return array<string, mixed>
     */
    private function rendicionDeLaura(Obra $obra, array $cambios = []): array
    {
        return [
            'tipo_documento' => 'sin_documento', 'origen' => 'sin_comprobante', 'fecha' => '2026-10-18', 'rendido_por' => 'Laura',
            'proveedor_id' => null,
            'renglones' => [
                $this->renglon('Combustible camioneta', '1', '68000.00', $obra, 'logistica', ['fecha' => '2026-10-15']),
                $this->renglon('Comida cuadrilla', '1', '42000.00', $obra, 'viaticos', ['fecha' => '2026-10-16']),
                $this->renglon('Peaje', '1', '3500.00', $obra, 'logistica', ['fecha' => '2026-10-16']),
            ],
            ...$cambios,
        ];
    }

    public function test_gasto_sin_comprobante_usa_el_proveedor_generico(): void
    {
        $nave = $this->obra('Piso industrial nave 3');

        $this->cargar($this->diego(), $this->rendicionDeLaura($nave))->assertCreated()
            ->assertJsonPath('data.total', '113500.00')
            ->assertJsonPath('data.documento', 'Sin documento')
            ->assertJsonPath('data.proveedor.es_generico', true)
            ->assertJsonPath('data.proveedor.nombre', 'Gastos sin comprobante');
    }

    public function test_cada_renglon_sin_documento_tiene_su_fecha(): void
    {
        $nave = $this->obra('Piso industrial nave 3');

        $this->cargar($this->diego(), $this->rendicionDeLaura($nave))->assertCreated()
            ->assertJsonPath('data.fecha', '2026-10-18')
            ->assertJsonPath('data.renglones.0.fecha', '2026-10-15')
            ->assertJsonPath('data.renglones.1.fecha', '2026-10-16');

        $sinFecha = $this->rendicionDeLaura($nave);
        unset($sinFecha['renglones'][0]['fecha']);
        $sinFecha['renglones'][1]['fecha'] = '2026-10-20';
        $this->cargar($this->diego(), $sinFecha)->assertUnprocessable()
            ->assertJsonPath('errors', $this->error('renglones.1.fecha', 'Renglón 2: la fecha no puede ser posterior a hoy.'));

        $sinFecha['renglones'][1]['fecha'] = '2026-10-16';
        $this->cargar($this->diego(), $sinFecha)->assertCreated()->assertJsonPath('data.renglones.0.fecha', '2026-10-18');
    }

    public function test_rendido_por_es_obligatorio_en_gastos_de_obra_rendidos(): void
    {
        $nave = $this->obra('Piso industrial nave 3');

        $this->cargar($this->diego(), $this->rendicionDeLaura($nave, ['rendido_por' => null]))->assertUnprocessable()
            ->assertJsonPath('errors.rendido_por.0', 'Elegí o escribí quién rindió.');
        $this->cargar($this->diego(), $this->rendicionDeLaura($nave, ['origen' => 'combustible', 'rendido_por' => null]))->assertCreated();
    }

    public function test_rendido_por_acepta_texto_libre(): void
    {
        $nave = $this->obra('Piso industrial nave 3');

        $this->cargar($this->diego(), $this->rendicionDeLaura($nave, ['rendido_por' => 'supervisor de obra Juan']))->assertCreated()
            ->assertJsonPath('data.rendido_por', 'supervisor de obra Juan');
    }

    public function test_sin_documento_admite_un_proveedor_de_la_base(): void
    {
        $nave = $this->obra('Piso industrial nave 3');
        $estacion = $this->proveedor('Estación de Servicio Ruta 3');

        $this->cargar($this->diego(), $this->rendicionDeLaura($nave, ['origen' => 'combustible', 'proveedor_id' => $estacion->id]))->assertCreated()
            ->assertJsonPath('data.proveedor.nombre', 'Estación de Servicio Ruta 3')
            ->assertJsonPath('data.numero', null);
    }

    public function test_consumo_de_stock_exige_obra(): void
    {
        $datos = ['origen' => 'consumo_stock', 'fecha' => '2026-10-18', 'renglones' => [$this->renglon('Cemento de stock', '5', '9800', null, null)]];

        $this->cargar($this->diego(), $datos)->assertUnprocessable()
            ->assertJsonPath('errors', $this->error('renglones.0.obra_id', 'Renglón 1: el consumo de stock va a una obra.'));
    }

    public function test_compra_a_stock_sin_obra_y_consumo_con_obra_no_se_cuentan_dos_veces(): void
    {
        $nave = $this->obra('Piso industrial nave 3');
        $diego = $this->diego();
        $this->cargado($diego, ['origen' => 'factura', 'numero' => '5-1', 'fecha' => '2026-10-10', 'proveedor_id' => $this->proveedor()->id,
            'renglones' => [$this->renglon('Cemento a stock', '50', '9800', null, null)]]);
        $this->cargado($diego, ['origen' => 'consumo_stock', 'fecha' => '2026-10-18',
            'renglones' => [$this->renglon('Cemento de stock', '5', '9800', $nave, 'materiales')]]);

        $this->assertSame([$nave->id => ['materiales' => '49000.00']], $this->app->make(GastadoDeObras::class)->totalesPorRubro([$nave->id]));
    }

    public function test_gasto_sin_comprobante_acepta_foto_de_tickets(): void
    {
        $nave = $this->obra('Piso industrial nave 3');
        $diego = $this->diego();
        $foto = $this->link($diego, 'comprobante_gasto', 'https://drive.example/tickets/tickets-octubre.heic');

        $this->cargar($diego, $this->rendicionDeLaura($nave, ['adjuntos' => [$foto]]))->assertCreated()
            ->assertJsonPath('data.adjuntos.0.nombre', 'tickets-octubre.heic');
    }

    public function test_sin_gastos_editar_no_rinde_gastos(): void
    {
        $nave = $this->obra('Piso industrial nave 3');

        $this->cargar($this->marta(), $this->rendicionDeLaura($nave))->assertForbidden();
    }
}
