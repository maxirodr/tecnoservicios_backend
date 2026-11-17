<?php

declare(strict_types=1);

use App\Http\Controllers\Api\ErrorLogController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Middleware\LimiteDelHealth;
use App\Shared\Providers\SharedServiceProvider;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

/*
 * Solo rutas transversales. Cada módulo registra las suyas en app/Modules/<Modulo>/routes.php
 * desde su ServiceProvider (no se tocan acá).
 */

// Health check: público, sin auth. Sin sesión ni limitador en Redis: responde aunque Redis esté caído (PU-020).
Route::get('/health', [HealthController::class, 'index'])
    ->withoutMiddleware(['throttle:'.SharedServiceProvider::LIMITE_API, EnsureFrontendRequestsAreStateful::class])
    ->middleware(LimiteDelHealth::class)
    ->name('health');

// Logs de errores para /bug-report: token propio (ERRORS_TOKEN), no auth de usuario.
Route::get('/errors', [ErrorLogController::class, 'index'])->name('errors.index');
