<?php

declare(strict_types=1);

namespace Gabrielesbaiz\UnsplashToolkit\Support;

use Gabrielesbaiz\UnsplashToolkit\Data\RateLimit;
use Gabrielesbaiz\UnsplashToolkit\Exceptions\RateLimitExceededException;
use Illuminate\Cache\RateLimiter;

/**
 * Keeps outgoing requests inside the hourly budget Unsplash grants.
 *
 * Demo applications get 50 requests an hour and approved ones 5000. Exceeding
 * it repeatedly gets access turned off, so the package refuses the request
 * locally rather than spending the budget discovering the limit.
 */
final class Throttle
{
    private ?RateLimit $lastSeen = null;

    /**
     * Create a new throttle.
     */
    public function __construct(
        private readonly Config $config,
        private readonly RateLimiter $limiter,
    ) {}

    /**
     * Reserve a slot for each outgoing request.
     */
    public function hit(int $requests = 1): void
    {
        if (! $this->config->rateLimitEnabled()) {
            return;
        }

        $key = $this->config->rateLimitKey();
        $max = $this->config->rateLimitPerHour();

        for ($i = 0; $i < $requests; $i++) {
            if ($this->limiter->tooManyAttempts($key, $max)) {
                // Cast for the same reason as used(): the store may hand back a string.
                throw RateLimitExceededException::make((int) $this->limiter->availableIn($key));
            }

            $this->limiter->hit($key, 3600);
        }
    }

    /**
     * Record the rate limit Unsplash reported on its last response.
     */
    public function remember(RateLimit $rateLimit): void
    {
        $this->lastSeen = $rateLimit;
    }

    /**
     * Get the rate limit Unsplash last reported, if any request has been made.
     */
    public function lastSeen(): ?RateLimit
    {
        return $this->lastSeen;
    }

    /**
     * Get the number of requests spent in the current window.
     *
     * RateLimiter::attempts() hands back whatever the cache store holds, and
     * Redis holds strings: an application on the redis driver got a TypeError
     * here where one on the array driver saw an int.
     */
    public function used(): int
    {
        return (int) $this->limiter->attempts($this->config->rateLimitKey());
    }

    /**
     * Get the number of requests left in the current window.
     */
    public function remaining(): int
    {
        return max(0, $this->config->rateLimitPerHour() - $this->used());
    }

    /**
     * Get the seconds until the current window resets.
     */
    public function availableIn(): int
    {
        return (int) $this->limiter->availableIn($this->config->rateLimitKey());
    }

    /**
     * Clear the rate limiter.
     */
    public function clear(): void
    {
        $this->limiter->clear($this->config->rateLimitKey());
    }
}
