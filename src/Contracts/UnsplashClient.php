<?php

declare(strict_types=1);

namespace Gabrielesbaiz\UnsplashToolkit\Contracts;

use Gabrielesbaiz\UnsplashToolkit\Data\RateLimit;

interface UnsplashClient
{
    /**
     * Send a GET request to the given Unsplash endpoint.
     *
     * @param  array<string, mixed>  $query
     * @return array<mixed>
     */
    public function get(string $endpoint, array $query = []): array;

    /**
     * Send several GET requests concurrently.
     *
     * @param  array<string, array{endpoint: string, query?: array<string, mixed>}>  $requests
     * @return array<string, array<mixed>|null>
     */
    public function pool(array $requests): array;

    /**
     * Request an absolute URL Unsplash supplied, such as a download location.
     *
     * @return array<mixed>
     */
    public function getAbsolute(string $url): array;

    /**
     * Get the rate limit Unsplash reported on the most recent response.
     */
    public function rateLimit(): ?RateLimit;
}
