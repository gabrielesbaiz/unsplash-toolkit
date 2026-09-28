<?php

declare(strict_types=1);

namespace Gabrielesbaiz\UnsplashToolkit\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Raised when a pool falls below its configured minimum size, so editors are
 * told to curate more photos before the pool runs dry at render time.
 */
final class PoolDepleted
{
    use Dispatchable;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly string $pool,
        public readonly int $remaining,
        public readonly int $minimum,
    ) {}
}
