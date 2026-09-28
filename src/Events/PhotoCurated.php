<?php

declare(strict_types=1);

namespace Gabrielesbaiz\UnsplashToolkit\Events;

use Gabrielesbaiz\UnsplashToolkit\Models\UnsplashAsset;
use Illuminate\Foundation\Events\Dispatchable;

final class PhotoCurated
{
    use Dispatchable;

    /**
     * Create a new event instance.
     */
    public function __construct(public readonly UnsplashAsset $asset) {}
}
