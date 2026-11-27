<?php

declare(strict_types=1);

use App\Modules\Gastos\Http\Controllers\AnularGastoController;
use App\Modules\Gastos\Http\Controllers\FiltrosDeGastosController;
use App\Modules\Gastos\Http\Controllers\GastoController;
use App\Modules\Gastos\Http\Controllers\GastoOpcionesController;
use App\Modules\Gastos\Http\Controllers\GastosCargadosController;
use App\Modules\Gastos\Http\Controllers\HistorialGastoController;
use App\Modules\Gastos\Http\Controllers\PagoDeFacturaController;
use App\Modules\Gastos\Http\Controllers\RenglonesSinDocumentoController;
use App\Modules\Gastos\Http\Middleware\PuedeCargarGastos;
use App\Modules\Gastos\Http\Middleware\PuedeCorregirGastos;
use App\Modules\Gastos\Http\Middleware\VerGastos;
use Illuminate\Support\Facades\Route;

// Rutas del módulo Gastos (prefijo /api, middleware 'api'), gastos.md §12.1. Ver y corregir por la fila
// `gastos` de la matriz (ver: `VerGastos`, con el 403 de §7); cargar según qué se carga (`PuedeCargarGastos`:
// gastos, rendicion o control_cantidad en Editar, resuelta DU-R05). El PATCH también lo usa
// `control_cantidad = editar`, solo para el remito de un retiro (§4.4, `PuedeCorregirGastos`). El alcance
// "solo sus obras" lo miran los casos de uso. No hay ruta para borrar gastos (RN-GAS-41).

$editar = 'permiso:gastos,editar';

Route::middleware('auth:sanctum')->group(function () use ($editar): void {
    Route::get('gastos', GastosCargadosController::class)->middleware(VerGastos::class);
    Route::get('gastos/filtros/obras', [FiltrosDeGastosController::class, 'obras'])->middleware(VerGastos::class);
    Route::get('gastos/filtros/proveedores', [FiltrosDeGastosController::class, 'proveedores'])->middleware(VerGastos::class);
    Route::get('gastos/opciones', [GastoOpcionesController::class, 'opciones'])->middleware(PuedeCargarGastos::class);
    Route::get('gastos/obras-opciones', [GastoOpcionesController::class, 'obras'])->middleware(PuedeCargarGastos::class);
    Route::get('gastos/factura-existente', [GastoOpcionesController::class, 'facturaExistente'])->middleware(PuedeCargarGastos::class);
    // Parte de horas (DU-C02): los nombres son de quien tiene `gastos = editar` (RN-GAS-56, 57).
    Route::get('gastos/empleados', [GastoOpcionesController::class, 'empleados'])->middleware($editar);

    Route::post('gastos', [GastoController::class, 'store'])->middleware([PuedeCargarGastos::class, 'idempotent']);
    Route::get('gastos/{gasto}', [GastoController::class, 'show'])->whereNumber('gasto')->middleware(VerGastos::class);
    Route::patch('gastos/{gasto}', [GastoController::class, 'update'])->whereNumber('gasto')->middleware(PuedeCorregirGastos::class);
    Route::post('gastos/{gasto}/anular', AnularGastoController::class)->whereNumber('gasto')->middleware([$editar, 'idempotent']);
    // Facturas pagas o impagas (DU-C03): `pagos = editar` y ver la factura (404 si no la ve).
    Route::post('gastos/{gasto}/marcar-paga', [PagoDeFacturaController::class, 'paga'])->whereNumber('gasto')->middleware(['permiso:pagos,editar', 'idempotent']);
    Route::post('gastos/{gasto}/marcar-impaga', [PagoDeFacturaController::class, 'impaga'])->whereNumber('gasto')->middleware(['permiso:pagos,editar', 'idempotent']);
    Route::get('gastos/{gasto}/historial', HistorialGastoController::class)->whereNumber('gasto')->middleware(VerGastos::class);

    // W-46: se abre desde la rendición; lo ven `rendicion` o `control_cantidad` (lo mira el caso de uso).
    Route::get('obras/{obra}/renglones-sin-documento', RenglonesSinDocumentoController::class)->whereNumber('obra');
});
