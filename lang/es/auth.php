<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Mensajes de ingreso
|--------------------------------------------------------------------------
|
| Los del framework para el ingreso. El sistema entra con código por mail
| (ADR-015) y sus mensajes viven en IngresarAction; estos quedan por si
| algún componente del framework los usa.
|
*/

return [

    'failed' => 'El mail o el código no son correctos.',
    'password' => 'La clave no es correcta.',
    'throttle' => 'Demasiados intentos. Probá de nuevo en :seconds segundos.',

];
