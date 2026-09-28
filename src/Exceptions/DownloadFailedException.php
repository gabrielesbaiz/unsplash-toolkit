<?php

declare(strict_types=1);

namespace Gabrielesbaiz\UnsplashToolkit\Exceptions;

final class DownloadFailedException extends UnsplashException
{
    /**
     * Create a new exception for a failed image transfer.
     */
    public static function make(string $id, string $reason): self
    {
        return new self("Failed to download the Unsplash photo [{$id}]: {$reason}");
    }
}
