<?php

declare(strict_types=1);

namespace Gabrielesbaiz\UnsplashToolkit\Exceptions;

final class PoolDepletedException extends UnsplashException
{
    /**
     * Create a new exception for a pool with no selectable photos.
     */
    public static function make(string $pool): self
    {
        return new self(
            "The curated Unsplash pool [{$pool}] has no active photos. "
            ."Curate some with \"php artisan unsplash:curate <id> --pool={$pool}\"."
        );
    }
}
