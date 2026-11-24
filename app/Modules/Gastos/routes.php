<?php

declare(strict_types=1);

use App\Modules\Gastos\Http\Controllers\AnularGastoController;
use App\Modules\Gastos\Http\Controllers\GastoController;
use App\Modules\Gastos\Http\Controllers\GastoOpcionesController;
use App\Modules\Gastos\Http\Controllers\GastosCargadosController;
use App\Modules\Gastos\Http\Controllers\HistorialGastoController;
use App\Modules\Gastos\Http\Controllers\RenglonesSinDocumentoController;
use App\Modules\Gastos\Http\Middleware\PuedeCargarGastos;
use Illuminate\Support\Facades\Route;

// Rutas del módulo Gastos (prefijo /api, middleware 'api'), gastos.md §12.1. Ver y corregir por la fila
// `gastos` de la matriz; cargar según qué se carga (`PuedeCargarGastos`: gastos, rendicion o
// control_cantidad en Editar, resuelta DU-R05). El alcance "solo sus obras" lo miran los casos de uso.
// No hay ruta para borrar gastos (RN-GAS-41).

$ver = 'permiso:gastos,ver';
$editar = 'permiso:gastos,editar';

Route::middleware('auth:sanctum')->group(function () use ($ver, $editar): void {
    Route::get('gastos', GastosCargadosController::class)->middleware($ver);
    Route::get('gastos/opciones', [GastoOpcionesController::class, 'opciones'])->middleware(PuedeCargarGastos::class);
    Route::get('gastos/obras-opciones', [GastoOpcionesController::class, 'obras'])->middleware(PuedeCargarGastos::class);
    Route::get('gastos/factura-existente', [GastoOpcionesController::class, 'facturaExistente'])->middleware(PuedeCargarGastos::class);

    Route::post('gastos', [GastoController::class, 'store'])->middleware([PuedeCargarGastos::class, 'idempotent']);
    Route::get('gastos/{gasto}', [GastoController::class, 'show'])->whereNumber('gasto')->middleware($ver);
    Route::patch('gastos/{gasto}', [GastoController::class, 'update'])->whereNumber('gasto')->middleware($editar);
    Route::post('gastos/{gasto}/anular', AnularGastoController::class)->whereNumber('gasto')->middleware([$editar, 'idempotent']);
    Route::get('gastos/{gasto}/historial', HistorialGastoController::class)->whereNumber('gasto')->middleware($ver);

    // W-46: se abre desde la rendición; lo ven `rendicion` o `control_cantidad` (lo mira el caso de uso).
    Route::get('obras/{obra}/renglones-sin-documento', RenglonesSinDocumentoController::class)->whereNumber('obra');
});
