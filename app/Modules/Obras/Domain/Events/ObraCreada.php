<?php

declare(strict_types=1);

namespace App\Modules\Obras\Domain\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

/** Se dio de alta una obra (obras.md §10.4). Sale después del commit. */
final readonly class ObraCreada implements ShouldDispatchAfterCommit
{
    public function __construct(public int $obraId) {}
}
