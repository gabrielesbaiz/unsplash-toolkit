<?php

declare(strict_types=1);

namespace Gabrielesbaiz\UnsplashToolkit\Support;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Repository;

/**
 * Caches Unsplash JSON metadata.
 *
 * Only API responses are cached. Image bytes are never cached or stored: they
 * are always served by Unsplash's CDN through a hotlinked URL.
 */
final class ResponseCache
{
    /**
     * Create a new response cache.
     */
    public function __construct(
        private readonly Config $config,
        private readonly CacheFactory $cache,
    ) {}

    /**
     * Determine if responses should be cached.
     */
    public function enabled(): bool
    {
        return $this->config->cacheEnabled();
    }

    /**
     * Get the cache store responses are written to.
     */
    public function store(): Repository
    {
        return $this->cache->store($this->config->cacheStore());
    }

    /**
     * Get the cache key for the given request.
     *
     * @param  array<string, mixed>  $query
     */
    public function key(string $endpoint, array $query = []): string
    {
        ksort($query);

        return sprintf(
            '%s:%s',
            $this->config->cachePrefix(),
            sha1($endpoint.'|'.json_encode($query)),
        );
    }

    /**
     * Get a cached response.
     *
     * @param  array<string, mixed>  $query
     * @return array<mixed>|null
     */
    public function get(string $endpoint, array $query = []): ?array
    {
        if (! $this->enabled()) {
            return null;
        }

        $cached = $this->store()->get($this->key($endpoint, $query));

        return is_array($cached) ? $cached : null;
    }

    /**
     * Cache a response.
     *
     * @param  array<string, mixed>  $query
     * @param  array<mixed>  $response
     */
    public function put(string $endpoint, array $query, array $response, ?int $ttl = null): void
    {
        if (! $this->enabled()) {
            return;
        }

        $this->store()->put(
            $this->key($endpoint, $query),
            $response,
            $ttl ?? $this->config->cacheTtl(),
        );
    }

    /**
     * Forget a cached response.
     *
     * @param  array<string, mixed>  $query
     */
    public function forget(string $endpoint, array $query = []): void
    {
        $this->store()->forget($this->key($endpoint, $query));
    }

    /**
     * Flush every cached response.
     *
     * The prefix is not a tag on every store, so this clears the whole store
     * only when the package has one to itself.
     */
    public function flush(): bool
    {
        return $this->store()->clear();
    }
}
