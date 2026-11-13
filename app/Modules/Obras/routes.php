<?php

declare(strict_types=1);

use App\Modules\Obras\Http\Controllers\DocumentoController;
use App\Modules\Obras\Http\Controllers\ObraController;
use App\Modules\Obras\Http\Controllers\ObraEstadoController;
use App\Modules\Obras\Http\Controllers\ObraHistorialController;
use App\Modules\Obras\Http\Controllers\PresupuestoController;
use App\Modules\Obras\Http\Controllers\PresupuestoMontosController;
use Illuminate\Support\Facades\Route;

// Rutas del módulo Obras (prefijo /api, middleware 'api'). Nivel por matriz con `permiso:<funcion>,<nivel>`;
// el alcance "solo sus obras", el responsable (estado) y las funciones de montos los verifican los casos de uso.

$ver = 'permiso:obras,ver';
$editar = 'permiso:obras,editar';

Route::middleware('auth:sanctum')->group(function () use ($ver, $editar): void {
    Route::get('obras', [ObraController::class, 'index'])->middleware($ver);
    Route::get('obras/clientes', [ObraController::class, 'clientes'])->middleware($ver);
    Route::post('obras', [ObraController::class, 'store'])->middleware([$editar, 'idempotent']);
    Route::get('obras/{obra}', [ObraController::class, 'show'])->whereNumber('obra')->middleware($ver);
    Route::patch('obras/{obra}', [ObraController::class, 'update'])->whereNumber('obra')->middleware($editar);
    Route::get('obras/{obra}/historial', ObraHistorialController::class)->whereNumber('obra')->middleware($ver);

    // Estado (W-22): el responsable de la obra o `estado_obra = editar` (RN-OBR-30).
    Route::get('obras/{obra}/pedidos-abiertos', [ObraEstadoController::class, 'pedidosAbiertos'])->whereNumber('obra')->middleware($ver);
    Route::post('obras/{obra}/estado', [ObraEstadoController::class, 'store'])->whereNumber('obra')->middleware([$ver, 'idempotent']);

    Route::get('obras/{obra}/presupuestos/sugerir-numero', [PresupuestoController::class, 'sugerirNumero'])->whereNumber('obra')->middleware($ver);
    Route::post('obras/{obra}/presupuestos', [PresupuestoController::class, 'store'])->whereNumber('obra')->middleware([$editar, 'idempotent']);
    Route::patch('presupuestos/{presupuesto}', [PresupuestoController::class, 'update'])->whereNumber('presupuesto')->middleware($editar);
    Route::delete('presupuestos/{presupuesto}', [PresupuestoController::class, 'destroy'])->whereNumber('presupuesto')->middleware($editar);

    // W-13: `presupuesto_rubro` y `monto_obra` con su alcance, sobre una obra visible.
    Route::get('obras/{obra}/presupuesto-por-rubro', [PresupuestoMontosController::class, 'show'])->whereNumber('obra')->middleware([$ver, 'permiso:presupuesto_rubro,ver']);
    Route::put('presupuestos/{presupuesto}/montos', [PresupuestoMontosController::class, 'montos'])->whereNumber('presupuesto')->middleware([$ver, 'permiso:presupuesto_rubro,editar']);
    Route::put('presupuestos/{presupuesto}/monto-obra', [PresupuestoMontosController::class, 'montoObra'])->whereNumber('presupuesto')->middleware([$ver, 'permiso:monto_obra,editar']);

    Route::post('obras/{obra}/documentos', [DocumentoController::class, 'store'])->whereNumber('obra')->middleware([$editar, 'idempotent']);
    Route::patch('documentos/{documento}', [DocumentoController::class, 'update'])->whereNumber('documento')->middleware($editar);
    Route::delete('documentos/{documento}', [DocumentoController::class, 'destroy'])->whereNumber('documento')->middleware($editar);
});
