<?php

declare(strict_types=1);

namespace Tests\Feature\Pedidos;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Pedidos\Domain\Models\Pedido;
use App\Modules\Pedidos\Infrastructure\Exportacion\PedidosAExcel;
use DateTimeInterface;
use OpenSpout\Reader\XLSX\Reader;
use Tests\Feature\Pedidos\Support\PedidosTestCase;

/** HU-042 c4 · RN-PED-45 · DU-P16: exportar a Excel todo lo filtrado, en el orden de la tabla y con el alcance. */
final class ExportarPedidosTest extends PedidosTestCase
{
    /**
     * Las filas del `.xlsx` (sin el encabezado), como textos; las fechas como "AAAA-MM-DD".
     *
     * @param  array<string, mixed>  $filtros
     * @return list<list<string>>
     */
    private function filas(User $user, array $filtros = []): array
    {
        $respuesta = $this->actingAs($user)->get('/api/pedidos/exportar?'.http_build_query($filtros))->assertOk();
        $archivo = tempnam(sys_get_temp_dir(), 'pedidos');
        $this->assertIsString($archivo);
        file_put_contents($archivo, $respuesta->streamedContent());
        $reader = new Reader;
        $reader->open($archivo);
        $filas = [];

        foreach ($reader->getSheetIterator() as $hoja) {
            foreach ($hoja->getRowIterator() as $fila) {
                $filas[] = array_map(static fn (mixed $v): string => $v instanceof DateTimeInterface ? $v->format('Y-m-d') : (is_scalar($v) ? (string) $v : ''), $fila->toArray());
            }
        }

        $reader->close();
        unlink($archivo);
        $this->assertSame(PedidosAExcel::ENCABEZADOS, $filas[0] ?? null);

        return array_slice($filas, 1);
    }

    public function test_exportar_baja_un_xlsx_con_nombre_del_dia(): void
    {
        $this->crear($this->laura(), $this->carla());

        $this->actingAs($this->laura())->get('/api/pedidos/exportar')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->assertDownload('tareas-2026-10-19.xlsx');
    }

    public function test_exportar_baja_todo_lo_filtrado_sin_paginar(): void
    {
        $laura = $this->laura();
        $carla = $this->carla();
        $obra = $this->obra(nombre: 'Piso industrial nave 3');
        $this->crear($laura, $carla, $obra, ['fecha_entrega' => '2026-10-30']);
        $this->crear($laura, $carla);
        Pedido::query()->insert(array_fill(0, 55, [
            'obra_id' => $obra->id, 'tarea' => 'Envío de EPP a obra', 'sector_id' => $carla->sector_id, 'propietario_id' => $carla->id,
            'solicitante_id' => $laura->id, 'fecha_pedido' => self::HOY, 'fecha_entrega' => '2026-10-25',
            'prioridad' => 'normal', 'estado' => 'no_iniciada', 'created_at' => now(), 'updated_at' => now(),
        ]));

        $filas = $this->filas($laura, ['obra_id' => $obra->id, 'estado' => 'abiertos']);

        $this->assertCount(56, $filas);
        $this->assertSame('Alquiler de andamio para el sector de bombas', $filas[55][1]);
    }

    public function test_exportar_trae_las_columnas_de_la_tabla_y_las_de_du_p16(): void
    {
        $laura = $this->laura();
        $carla = $this->carla();
        $obra = $this->obra();
        $pedido = $this->crear($laura, $carla, $obra, [
            'periodo_inicio' => '2026-10-26', 'periodo_fin' => '2026-11-06', 'aclaraciones' => 'Andamio tubular para unos 40 m².',
        ]);
        $this->accion($carla, $pedido, 'completar', ['comentario' => 'Entregado en obra, recibió el encargado.'])->assertOk();
        $enSector = $this->crear($laura, $carla, null, ['fecha_entrega' => '2026-10-24']);
        $this->accion($carla, $enSector, 'reasignar', ['sector_id' => $carla->sector_id])->assertOk();
        $obra->refresh()->load('principal');

        $filas = $this->filas($laura, ['estado' => 'todos']);

        $this->assertSame([
            'Reparación de losa tanque B', 'Alquiler de andamio para el sector de bombas', 'Compras', 'Carla', 'Laura',
            self::HOY, '2026-10-23', 'Prioritario', 'Completada', (string) $obra->principal?->numero,
            'Alquileres Andamios Oeste', '26/10/2026 al 06/11/2026', 'Andamio tubular para unos 40 m².', self::HOY,
        ], $filas[0]);
        $this->assertSame(['Servicios generales', 'Compras (sector)', '', ''], [$filas[1][0], $filas[1][3], $filas[1][9], $filas[1][13]]);
    }

    public function test_exportar_marca_al_propietario_inactivo_como_la_tabla(): void
    {
        $laura = $this->laura();
        $carla = $this->carla();
        $this->crear($laura, $carla);
        $carla->forceFill(['activo' => false])->save();

        // Quien reparte el trabajo desde el Excel tiene que ver que esa persona ya no está (PU-612).
        $this->assertSame('Carla (inactivo)', $this->filas($laura)[0][3] ?? null);
    }

    public function test_exportar_respeta_el_alcance(): void
    {
        $this->celda('Calidad', 'pedidos', 'ver', 'propias');
        $sofia = $this->sofia();
        $laura = $this->laura();
        $this->crear($laura, $sofia, null, ['tarea' => 'Envío de EPP a obra']);
        $this->crear($laura, $this->carla());

        $filas = $this->filas($sofia);

        $this->assertCount(1, $filas);
        $this->assertSame('Envío de EPP a obra', $filas[0][1]);
    }

    public function test_exportar_con_filtros_invalidos_devuelve_422(): void
    {
        $this->actingAs($this->laura())->getJson('/api/pedidos/exportar?vencidos=talvez')->assertUnprocessable()->assertJsonValidationErrors(['vencidos'], 'errors');
    }
}
