<?php

declare(strict_types=1);

namespace Tests\Unit\Shared;

use App\Shared\Http\ApiResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use PHPUnit\Framework\TestCase;

/** TL-03: el listado paginado suma datos extra en `meta` sin que el controller desarme el sobre. */
final class ListadoPaginadoConMetaTest extends TestCase
{
    public function test_listado_paginado_suma_datos_extra_en_meta_dentro_del_sobre(): void
    {
        $pagina = new LengthAwarePaginator([['id' => 1], ['id' => 2]], 7, 2, 1, ['path' => '/api/pedidos']);

        /** @var array{data: list<mixed>, message: string|null, errors: mixed, meta: array<string, mixed>, links: array<string, mixed>} $cuerpo */
        $cuerpo = ApiResponse::paginated($pagina, null, ['abiertas' => 6, 'vencidas' => 2])->getData(true);

        $this->assertSame([['id' => 1], ['id' => 2]], $cuerpo['data']);
        $this->assertNull($cuerpo['errors']);
        $this->assertSame(7, $cuerpo['meta']['total']);
        $this->assertSame(6, $cuerpo['meta']['abiertas']);
        $this->assertSame(2, $cuerpo['meta']['vencidas']);
        $this->assertArrayHasKey('next', $cuerpo['links']);
    }

    public function test_las_filas_armadas_por_el_controller_reemplazan_las_de_la_pagina(): void
    {
        $pagina = new LengthAwarePaginator([['id' => 1]], 1, 20, 1, ['path' => '/api/obras/1/historial']);

        /** @var array{data: list<mixed>} $cuerpo */
        $cuerpo = ApiResponse::paginated($pagina, [['que' => 'Creó la obra'], ['que' => 'Tarea: a → b']])->getData(true);

        $this->assertSame([['que' => 'Creó la obra'], ['que' => 'Tarea: a → b']], $cuerpo['data']);
    }
}
