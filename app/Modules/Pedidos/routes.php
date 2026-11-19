<?php

declare(strict_types=1);

use App\Modules\Pedidos\Http\Controllers\ComentarioController;
use App\Modules\Pedidos\Http\Controllers\InicioController;
use App\Modules\Pedidos\Http\Controllers\PedidoController;
use App\Modules\Pedidos\Http\Controllers\PedidoEstadoController;
use App\Modules\Pedidos\Http\Controllers\PedidoOpcionesController;
use App\Modules\Pedidos\Http\Controllers\RecorridoController;
use App\Modules\Pedidos\Http\Controllers\SeguirController;
use Illuminate\Support\Facades\Route;

// Rutas del módulo Pedidos (prefijo /api, middleware 'api'). Nivel por matriz con `permiso:pedidos,<nivel>`
// donde la acción es fila de la matriz (crear, editar). Lo propio (Inicio, detalle, comentar, transiciones)
// no pasa por el middleware: el alcance y las reglas fijas (§7.2) los verifican los casos de uso.
// No hay ruta para borrar pedidos (RN-PED-30) ni comentarios (RN-PED-25).

$editar = 'permiso:pedidos,editar';

Route::middleware('auth:sanctum')->group(function () use ($editar): void {
    Route::get('pedidos/inicio', [InicioController::class, 'inicio']);
    Route::get('pedidos/mis-tareas', [InicioController::class, 'misTareas']);
    Route::get('pedidos/que-pedi', [InicioController::class, 'quePedi']);
    Route::get('pedidos/que-sigo', [InicioController::class, 'queSigo']);
    Route::get('pedidos/prioridad-sugerida', [PedidoOpcionesController::class, 'prioridadSugerida']);
    Route::get('pedidos/obras-opciones', [PedidoOpcionesController::class, 'obras'])->middleware($editar);

    Route::post('pedidos', [PedidoController::class, 'store'])->middleware([$editar, 'idempotent']);
    Route::get('pedidos/{pedido}', [PedidoController::class, 'show'])->whereNumber('pedido');
    Route::patch('pedidos/{pedido}', [PedidoController::class, 'update'])->whereNumber('pedido')->middleware($editar);
    Route::get('pedidos/{pedido}/recorrido', RecorridoController::class)->whereNumber('pedido');

    foreach (['tomar', 'bloquear', 'retomar', 'completar', 'reabrir', 'reasignar'] as $accion) {
        Route::post("pedidos/{pedido}/{$accion}", [PedidoEstadoController::class, $accion])->whereNumber('pedido')->middleware('idempotent');
    }

    Route::post('pedidos/{pedido}/comentarios', [ComentarioController::class, 'store'])->whereNumber('pedido')->middleware('idempotent');
    Route::post('pedidos/{pedido}/seguir', [SeguirController::class, 'store'])->whereNumber('pedido')->middleware('idempotent');
    Route::delete('pedidos/{pedido}/seguir', [SeguirController::class, 'destroy'])->whereNumber('pedido');
});
