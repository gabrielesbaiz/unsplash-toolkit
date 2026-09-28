<?php

declare(strict_types=1);

namespace Gabrielesbaiz\UnsplashToolkit\Exceptions;

final class PhotoNotFoundException extends UnsplashException
{
    /**
     * Create a new exception for a photo Unsplash no longer serves.
     */
    public static function make(string $id): self
    {
        return new self("The Unsplash photo [{$id}] could not be found. It may have been removed by the photographer.");
    }
}
