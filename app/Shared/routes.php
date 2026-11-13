<?php

declare(strict_types=1);

use App\Shared\Adjuntos\Http\AdjuntoController;
use App\Shared\Busqueda\Http\BuscadorController;
use App\Shared\Providers\SharedServiceProvider;
use Illuminate\Support\Facades\Route;

/*
 * Rutas transversales de Shared (prefijo /api, middleware api), registradas por SharedServiceProvider.
 */

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/adjuntos', [AdjuntoController::class, 'store'])
        ->middleware(['throttle:'.SharedServiceProvider::LIMITE_SUBIDAS, 'idempotent'])
        ->name('adjuntos.store');

    Route::get('/adjuntos/{adjunto}', [AdjuntoController::class, 'show'])
        ->whereNumber('adjunto')
        ->name('adjuntos.show');

    Route::delete('/adjuntos/{adjunto}', [AdjuntoController::class, 'destroy'])
        ->whereNumber('adjunto')
        ->name('adjuntos.destroy');

    Route::get('/buscar', BuscadorController::class)->name('buscar');
});
