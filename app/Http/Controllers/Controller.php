<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Api\Traits\SafeErrorResponse;
use App\Shared\Http\RespondsWithEnvelope;

/**
 * Base de todo controller (también los de los módulos): envelope { data, message, errors }
 * y safeError() para los catch explícitos.
 */
abstract class Controller
{
    use RespondsWithEnvelope;
    use SafeErrorResponse;
}
