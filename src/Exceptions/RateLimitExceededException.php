<?php

declare(strict_types=1);

namespace Gabrielesbaiz\UnsplashToolkit\Exceptions;

final class RateLimitExceededException extends UnsplashException
{
    /**
     * Create a new rate limit exception.
     */
    public function __construct(public readonly ?int $retryAfter = null, string $message = '')
    {
        parent::__construct($message !== '' ? $message : self::describe($retryAfter));
    }

    /**
     * Create a new exception from an Unsplash response.
     */
    public static function make(?int $retryAfter = null): self
    {
        return new self($retryAfter);
    }

    /**
     * Build the default message.
     */
    private static function describe(?int $retryAfter): string
    {
        $suffix = $retryAfter !== null
            ? " Retry in {$retryAfter} seconds."
            : ' Check your hourly budget with "php artisan unsplash:status".';

        return 'The Unsplash API rate limit has been reached.'.$suffix
            .' Curated pools are read from your database, so rendering does not consume this budget.';
    }
}
