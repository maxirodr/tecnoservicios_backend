<?php

declare(strict_types=1);

use App\Modules\Identidad\Http\Controllers\IngresoController;
use App\Modules\Identidad\Http\Controllers\PerfilController;
use App\Modules\Identidad\Http\Controllers\PermisoController;
use App\Modules\Identidad\Http\Controllers\PlazosPrioridadController;
use App\Modules\Identidad\Http\Controllers\RecuperacionController;
use App\Modules\Identidad\Http\Controllers\SectorController;
use App\Modules\Identidad\Http\Controllers\UsuarioController;
use App\Modules\Identidad\IdentidadServiceProvider;
use Illuminate\Support\Facades\Route;

// Rutas del módulo Identidad (prefijo /api, middleware 'api'). Permisos por matriz: `permiso:<funcion>,<nivel>` (identidad.md §6.5).

$verUsuarios = 'permiso:usuarios_permisos,ver';
$editarUsuarios = 'permiso:usuarios_permisos,editar';

Route::prefix('auth')->group(function (): void {
    Route::post('ingresar', [IngresoController::class, 'store'])->middleware('throttle:'.IdentidadServiceProvider::LIMITE_INGRESO);
    Route::post('salir', [IngresoController::class, 'destroy'])->middleware('auth:sanctum');
    Route::post('olvide-contrasena', [RecuperacionController::class, 'store'])->middleware('throttle:'.IdentidadServiceProvider::LIMITE_RECUPERACION);
    Route::post('elegir-contrasena', [RecuperacionController::class, 'update']);
});

Route::middleware('auth:sanctum')->group(function () use ($verUsuarios, $editarUsuarios): void {
    Route::get('me', [PerfilController::class, 'show']);
    Route::patch('me', [PerfilController::class, 'update']);
    Route::put('me/contrasena', [PerfilController::class, 'cambiarContrasena']);

    Route::get('usuarios/opciones', [UsuarioController::class, 'opciones']);
    Route::get('usuarios', [UsuarioController::class, 'index'])->middleware($verUsuarios);
    Route::post('usuarios', [UsuarioController::class, 'store'])->middleware($editarUsuarios);
    Route::get('usuarios/{usuario}', [UsuarioController::class, 'show'])->whereNumber('usuario')->middleware($verUsuarios);
    Route::patch('usuarios/{usuario}', [UsuarioController::class, 'update'])->whereNumber('usuario')->middleware($editarUsuarios);
    Route::post('usuarios/{usuario}/reenviar-bienvenida', [UsuarioController::class, 'reenviarBienvenida'])
        ->whereNumber('usuario')
        ->middleware([$editarUsuarios, 'throttle:'.IdentidadServiceProvider::LIMITE_BIENVENIDA]);

    Route::get('sectores', [SectorController::class, 'index']);
    Route::post('sectores', [SectorController::class, 'store'])->middleware($editarUsuarios);
    Route::patch('sectores/{sector}', [SectorController::class, 'update'])->whereNumber('sector')->middleware($editarUsuarios);
    Route::delete('sectores/{sector}', [SectorController::class, 'destroy'])->whereNumber('sector')->middleware($editarUsuarios);

    Route::get('permisos', [PermisoController::class, 'index'])->middleware($verUsuarios);
    Route::put('permisos', [PermisoController::class, 'update'])->middleware($editarUsuarios);

    Route::get('plazos-prioridad', [PlazosPrioridadController::class, 'show'])->middleware('permiso:plazos_prioridad,ver');
    Route::put('plazos-prioridad', [PlazosPrioridadController::class, 'update'])->middleware('permiso:plazos_prioridad,editar');
});
