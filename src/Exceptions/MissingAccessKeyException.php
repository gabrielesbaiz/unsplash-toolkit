<?php

declare(strict_types=1);

namespace Gabrielesbaiz\UnsplashToolkit\Exceptions;

final class MissingAccessKeyException extends UnsplashException
{
    /**
     * Create a new exception for an absent access key.
     */
    public static function make(): self
    {
        return new self(
            'No Unsplash access key is configured. Set UNSPLASH_ACCESS_KEY in your environment, '
            .'or publish the config with "php artisan vendor:publish --tag=unsplash-toolkit-config".'
        );
    }
}
