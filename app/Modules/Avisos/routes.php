<?php

declare(strict_types=1);

use App\Modules\Avisos\Http\Controllers\AvisoController;
use App\Modules\Avisos\Http\Controllers\AvisosPorMailController;
use Illuminate\Support\Facades\Route;

// Rutas del módulo Avisos (prefijo /api, middleware 'api'). Los avisos no son una fila de la matriz: cada
// persona ve y marca solo los suyos (avisos.md §7); un aviso ajeno responde 404.

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('avisos/no-leidos/contador', [AvisoController::class, 'contador']);
    Route::get('avisos', [AvisoController::class, 'index']);
    Route::post('avisos/leer-todos', [AvisoController::class, 'leerTodos']);
    Route::post('avisos/{aviso}/leido', [AvisoController::class, 'leido'])->whereNumber('aviso');

    Route::get('me/avisos-por-mail', [AvisosPorMailController::class, 'show']);
    Route::put('me/avisos-por-mail', [AvisosPorMailController::class, 'update']);
});
