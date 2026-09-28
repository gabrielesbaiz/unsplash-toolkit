<?php

declare(strict_types=1);

namespace Gabrielesbaiz\UnsplashToolkit\Exceptions;

/**
 * Attribution links carry your application name as their utm_source. Emitting a
 * credit without one would not satisfy the API Guidelines, so it fails loudly.
 */
final class MissingAttributionNameException extends UnsplashException
{
    /**
     * Create a new exception for a missing or placeholder application name.
     */
    public static function make(): self
    {
        return new self(
            'No application name is configured for Unsplash attribution. Set UNSPLASH_APP_NAME to '
            .'your real application name: it becomes the utm_source of every photographer credit, '
            .'which the Unsplash API Guidelines require. See https://help.unsplash.com/api-guidelines'
        );
    }
}
