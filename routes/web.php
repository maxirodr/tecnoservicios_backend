<?php

declare(strict_types=1);

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

// API-only: la raíz no expone nada del stack ni abre sesión.
Route::get('/', fn (): JsonResponse => new JsonResponse(['status' => 'ok']))->withoutMiddleware('web');
