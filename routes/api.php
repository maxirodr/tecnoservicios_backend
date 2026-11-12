<?php

declare(strict_types=1);

use App\Http\Controllers\Api\ErrorLogController;
use App\Http\Controllers\Api\HealthController;
use Illuminate\Support\Facades\Route;

/*
 * Solo rutas transversales. Cada módulo registra las suyas en app/Modules/<Modulo>/routes.php
 * desde su ServiceProvider (no se tocan acá).
 */

// Health check: público, sin auth.
Route::get('/health', [HealthController::class, 'index'])->name('health');

// Logs de errores para /bug-report: token propio (ERRORS_TOKEN), no auth de usuario.
Route::get('/errors', [ErrorLogController::class, 'index'])->name('errors.index');
