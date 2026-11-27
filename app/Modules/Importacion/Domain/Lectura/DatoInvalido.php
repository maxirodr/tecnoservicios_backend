<?php

declare(strict_types=1);

namespace App\Modules\Importacion\Domain\Lectura;

use DomainException;

/** Un valor de una celda que no se entiende (RN-IMP-14): el mensaje es el faltante del informe, con el valor. */
final class DatoInvalido extends DomainException {}
