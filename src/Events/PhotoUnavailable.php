<?php

declare(strict_types=1);

namespace Gabrielesbaiz\UnsplashToolkit\Events;

use Gabrielesbaiz\UnsplashToolkit\Models\UnsplashAsset;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Raised when a curated photo no longer resolves on Unsplash.
 *
 * Without local copies this is how a removed photo stops being served: the
 * asset is marked unavailable and pool selection skips it.
 */
final class PhotoUnavailable
{
    use Dispatchable;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly UnsplashAsset $asset,
        public readonly string $reason = '',
    ) {}
}
