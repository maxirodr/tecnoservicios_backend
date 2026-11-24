<?php

declare(strict_types=1);

namespace Tests\Feature\Rendicion;

use App\Modules\Obras\Domain\Enums\ObraEstado;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Rendicion\Support\RendicionTestCase;
use Tests\Feature\Rendicion\Support\Renglon;

/** F-53 · HU-080 (W-12): rendicion.md RN-REN-38…41. */
final class RendicionesEnCursoTest extends RendicionTestCase
{
    public function test_rendiciones_en_curso_solo_en_ejecucion_ordenadas_por_porcentaje_y_sin_presupuesto_al_final(): void
    {
        $diego = $this->diego();
        $sinPresupuesto = $this->obraDe($diego, numero: '1-100-2026');
        $baja = $this->obraDe($diego, numero: '1-300-2026');
        $alta = $this->obraDe($diego, numero: '1-200-2026');
        $this->obraDe($diego, ObraEstado::Confirmada);
        $this->obraDe($diego, ObraEstado::Terminada);
        $this->porRubro($this->principal($baja), ['materiales' => '1000.00']);
        $this->soloTotal($this->principal($alta), '1000.00');
        $this->gastado->agregar($baja->id, Renglon::de(1, 1, '2026-10-02', 'factura', 'Cemento', '100.00', 'materiales'));
        $this->gastado->agregar($alta->id, Renglon::de(2, 2, '2026-10-02', 'factura', 'Cemento', '1100.00', 'materiales'));
        $this->gastado->agregar($sinPresupuesto->id, Renglon::de(3, 3, '2026-10-02', 'factura', 'Cemento', '50.00', 'materiales'));

        $data = $this->actingAs($diego)->getJson('/api/rendiciones')->assertOk()->json('data');

        $this->assertSame(
            [[$alta->id, '110.00', true, null], [$baja->id, '10.00', false, 0], [$sinPresupuesto->id, null, false, null]],
            array_map(static fn (array $f): array => [$f['obra']['id'], $f['porcentaje'], $f['pasado'], $f['rubros_pasados']], $data['obras']),
        );
        $this->assertSame(['presupuestado' => '2000.00', 'gastado' => '1250.00', 'porcentaje' => '62.50', 'rubros_pasados' => 0], $data['total']);
    }

    public function test_sin_obras_en_ejecucion_devuelve_lista_vacia(): void
    {
        $this->actingAs($this->diego())->getJson('/api/rendiciones')
            ->assertOk()
            ->assertJsonPath('data.obras', [])
            ->assertJsonPath('data.total', ['presupuestado' => '0.00', 'gastado' => '0.00', 'porcentaje' => null, 'rubros_pasados' => null]);
    }

    public function test_rendiciones_en_curso_no_hace_una_consulta_por_obra(): void
    {
        $diego = $this->diego();
        $this->obraDe($diego);
        $this->actingAs($diego)->getJson('/api/rendiciones')->assertOk();
        $conUna = $this->consultas(fn () => $this->getJson('/api/rendiciones')->assertOk());

        foreach (range(1, 14) as $i) {
            $obra = $this->obraDe($diego);
            $this->porRubro($this->principal($obra), ['materiales' => '1000.00']);
        }
        $conQuince = $this->consultas(fn () => $this->getJson('/api/rendiciones')->assertOk()->assertJsonCount(15, 'data.obras'));

        $this->assertSame($conUna, $conQuince);
        $this->gastado->consultasDeTotales = 0;
        $this->getJson('/api/rendiciones');
        $this->assertSame(1, $this->gastado->consultasDeTotales);
    }

    private function consultas(callable $pedido): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $pedido();
        $cantidad = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $cantidad;
    }
}
