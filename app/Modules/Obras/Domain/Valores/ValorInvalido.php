<?php

declare(strict_types=1);

namespace App\Modules\Obras\Domain\Valores;

use DomainException;

/** Lo escrito no se puede normalizar (RN-OBR-11, RN-OBR-14). El mensaje va tal cual al 422. */
final class ValorInvalido extends DomainException {}
