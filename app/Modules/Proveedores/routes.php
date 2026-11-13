<?php

declare(strict_types=1);

use App\Modules\Proveedores\Http\Controllers\GastadoPorObraController;
use App\Modules\Proveedores\Http\Controllers\OpcionesProveedoresController;
use App\Modules\Proveedores\Http\Controllers\ParecidosController;
use App\Modules\Proveedores\Http\Controllers\ProveedorController;
use Illuminate\Support\Facades\Route;

// Rutas del módulo Proveedores (prefijo /api, middleware 'api'). Permisos por matriz (proveedores.md §7).

$ver = 'permiso:proveedores,ver';
$editar = 'permiso:proveedores,editar';

Route::middleware('auth:sanctum')->group(function () use ($ver, $editar): void {
    Route::get('proveedores', [ProveedorController::class, 'index'])->middleware($ver);
    // Proveedores ≥ ver o Gastos = editar: lo resuelve el FormRequest.
    Route::get('proveedores/opciones', OpcionesProveedoresController::class);
    Route::get('proveedores/parecidos', ParecidosController::class)->middleware($ver);
    Route::post('proveedores', [ProveedorController::class, 'store'])->middleware([$editar, 'idempotent']);
    Route::get('proveedores/{proveedor}', [ProveedorController::class, 'show'])->whereNumber('proveedor')->middleware($ver);
    Route::patch('proveedores/{proveedor}', [ProveedorController::class, 'update'])->whereNumber('proveedor')->middleware($editar);
    Route::get('proveedores/{proveedor}/gastado-por-obra', GastadoPorObraController::class)
        ->whereNumber('proveedor')
        ->middleware([$ver, 'permiso:gastos,ver']);
});
