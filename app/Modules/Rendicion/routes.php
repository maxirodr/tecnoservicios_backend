<?php

declare(strict_types=1);

use App\Modules\Rendicion\Http\Controllers\MaterialController;
use App\Modules\Rendicion\Http\Controllers\RendicionController;
use Illuminate\Support\Facades\Route;

// Rutas del módulo Rendicion (prefijo /api, middleware 'api'), rendicion.md §12. La rendición y W-12 verifican
// `rendicion` ≥ ver en `AccesoRendicion` (403 "No tenés acceso a la rendición.", 404 fuera de alcance). La lista
// de materiales pide `control_cantidad = editar` por matriz y el alcance en el caso de uso.

$editarLista = 'permiso:control_cantidad,editar';

Route::middleware('auth:sanctum')->group(function () use ($editarLista): void {
    Route::get('rendiciones', [RendicionController::class, 'enCurso']);
    Route::get('obras/{obra}/rendicion', [RendicionController::class, 'show'])->whereNumber('obra');
    Route::get('obras/{obra}/rendicion/rubros/{clave}', [RendicionController::class, 'rubro'])->whereNumber('obra');
    Route::get('obras/{obra}/rendicion/por-proveedor', [RendicionController::class, 'porProveedor'])->whereNumber('obra');

    Route::get('obras/{obra}/control-cantidad', [MaterialController::class, 'control'])->whereNumber('obra');
    Route::get('obras/{obra}/materiales/opciones', [MaterialController::class, 'opciones'])->whereNumber('obra');
    Route::post('obras/{obra}/materiales', [MaterialController::class, 'store'])->whereNumber('obra')->middleware([$editarLista, 'idempotent']);
    Route::patch('materiales/{material}', [MaterialController::class, 'update'])->whereNumber('material')->middleware($editarLista);
    Route::delete('materiales/{material}', [MaterialController::class, 'destroy'])->whereNumber('material')->middleware($editarLista);
});
