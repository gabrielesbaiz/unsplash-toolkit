<?php

declare(strict_types=1);

namespace Gabrielesbaiz\UnsplashToolkit\Support;

use Illuminate\Contracts\Config\Repository;

/**
 * Typed access to the package configuration.
 *
 * Every read goes through self::KEY, so the published file name and the key the
 * package reads from can never drift apart again.
 */
final readonly class Config
{
    public const KEY = 'unsplash-toolkit';

    /**
     * The placeholder shipped in older config files, which is not a usable utm_source.
     */
    public const APP_NAME_PLACEHOLDER = 'your_app_name';

    /**
     * Create a new configuration instance.
     */
    public function __construct(private Repository $repository) {}

    /**
     * Get the given configuration value.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->repository->get(self::KEY.'.'.$key, $default);
    }

    /**
     * Set the given configuration value.
     */
    public function set(string $key, mixed $value): void
    {
        $this->repository->set(self::KEY.'.'.$key, $value);
    }

    /**
     * Get the Unsplash access key.
     */
    public function accessKey(): ?string
    {
        $key = $this->get('access_key');

        return filled($key) ? (string) $key : null;
    }

    /**
     * Get the Unsplash secret key.
     */
    public function secretKey(): ?string
    {
        $key = $this->get('secret_key');

        return filled($key) ? (string) $key : null;
    }

    /**
     * Get the base URL of the Unsplash API.
     */
    public function baseUrl(): string
    {
        return rtrim((string) $this->get('base_url', 'https://api.unsplash.com'), '/');
    }

    /**
     * Get the Unsplash API version to request.
     */
    public function apiVersion(): string
    {
        return (string) $this->get('api_version', 'v1');
    }

    /**
     * Get the application name used as the utm_source of attribution links.
     */
    public function appName(): ?string
    {
        $name = $this->get('compliance.app_name');

        if (blank($name) || $name === self::APP_NAME_PLACEHOLDER) {
            return null;
        }

        return (string) $name;
    }

    /**
     * Determine if downloading image bytes to a local disk is permitted.
     */
    public function allowsLocalStorage(): bool
    {
        return (bool) $this->get('compliance.allow_local_storage', false);
    }

    /**
     * Get the reference to the written permission Unsplash granted for local storage.
     */
    public function storagePermissionReference(): ?string
    {
        $reference = $this->get('compliance.storage_permission_reference');

        return filled($reference) ? (string) $reference : null;
    }

    /**
     * Get the request timeout in seconds.
     */
    public function timeout(): int
    {
        return (int) $this->get('http.timeout', 10);
    }

    /**
     * Get the connection timeout in seconds.
     */
    public function connectTimeout(): int
    {
        return (int) $this->get('http.connect_timeout', 5);
    }

    /**
     * Get the maximum number of requests a pooled batch fires at once.
     */
    public function concurrency(): int
    {
        return max(1, (int) $this->get('http.concurrency', 5));
    }

    /**
     * Get the retry configuration.
     *
     * @return array{times: int, sleep: int, backoff: bool}
     */
    public function retry(): array
    {
        return [
            'times' => (int) $this->get('http.retry.times', 3),
            'sleep' => (int) $this->get('http.retry.sleep', 250),
            'backoff' => (bool) $this->get('http.retry.backoff', true),
        ];
    }

    /**
     * Determine if outgoing requests are rate limited.
     */
    public function rateLimitEnabled(): bool
    {
        return (bool) $this->get('rate_limit.enabled', true);
    }

    /**
     * Get the rate limiter key.
     */
    public function rateLimitKey(): string
    {
        return (string) $this->get('rate_limit.key', 'unsplash-toolkit');
    }

    /**
     * Get the number of requests allowed each hour.
     */
    public function rateLimitPerHour(): int
    {
        return (int) $this->get('rate_limit.max_per_hour', 50);
    }

    /**
     * Determine if API responses should be cached.
     */
    public function cacheEnabled(): bool
    {
        return (bool) $this->get('cache.enabled', true);
    }

    /**
     * Get the cache store responses are written to.
     */
    public function cacheStore(): ?string
    {
        $store = $this->get('cache.store');

        return filled($store) ? (string) $store : null;
    }

    /**
     * Get the cache lifetime in seconds.
     */
    public function cacheTtl(): int
    {
        return (int) $this->get('cache.ttl', 3600);
    }

    /**
     * Get the cache key prefix.
     */
    public function cachePrefix(): string
    {
        return (string) $this->get('cache.prefix', 'unsplash');
    }

    /**
     * Get the name of the pool used when none is given.
     */
    public function defaultPool(): string
    {
        return (string) $this->get('pools.default', 'default');
    }

    /**
     * Get how long a selected photo stays cached, in seconds.
     */
    public function selectionTtl(): int
    {
        return (int) $this->get('pools.selection_ttl', 600);
    }

    /**
     * Get the size below which a pool is considered depleted.
     */
    public function poolMinSize(): int
    {
        return (int) $this->get('pools.min_size', 5);
    }

    /**
     * Get the colour rendered when a pool has nothing to show.
     */
    public function fallbackColor(): string
    {
        return (string) $this->get('pools.fallback_color', '#0f172a');
    }

    /**
     * Get the default image quality.
     */
    public function imageQuality(): int
    {
        return (int) $this->get('images.quality', 80);
    }

    /**
     * Get the default image format.
     */
    public function imageFormat(): ?string
    {
        $format = $this->get('images.format', 'jpg');

        return filled($format) ? (string) $format : null;
    }

    /**
     * Get the default crop mode.
     */
    public function imageFit(): ?string
    {
        $fit = $this->get('images.fit', 'crop');

        return filled($fit) ? (string) $fit : null;
    }

    /**
     * Get the widths a responsive srcset is generated for.
     *
     * @return array<int, int>
     */
    public function srcsetWidths(): array
    {
        /** @var array<int, int|string> $widths */
        $widths = $this->get('images.srcset_widths', [640, 960, 1280, 1920, 2560]);

        return array_values(array_map(intval(...), $widths));
    }

    /**
     * Determine if images are rendered with lazy loading.
     */
    public function lazyImages(): bool
    {
        return (bool) $this->get('images.lazy', true);
    }

    /**
     * Get the database connection the package models use.
     */
    public function connection(): ?string
    {
        $connection = $this->get('database.connection');

        return filled($connection) ? (string) $connection : null;
    }

    /**
     * Get the table curated photos are stored in.
     */
    public function assetsTable(): string
    {
        return (string) $this->get('database.assets_table', 'unsplash_assets');
    }

    /**
     * Get the polymorphic pivot table name.
     */
    public function pivotTable(): string
    {
        return (string) $this->get('database.pivot_table', 'unsplashables');
    }

    /**
     * Determine if the picker proxy route is registered.
     */
    public function pickerEnabled(): bool
    {
        return (bool) $this->get('picker.enabled', true);
    }

    /**
     * Get the URI prefix of the picker proxy route.
     */
    public function pickerPrefix(): string
    {
        return trim((string) $this->get('picker.route_prefix', 'unsplash-toolkit'), '/');
    }

    /**
     * Get the middleware protecting the picker proxy route.
     *
     * @return array<int, string>
     */
    public function pickerMiddleware(): array
    {
        /** @var array<int, string> $middleware */
        $middleware = $this->get('picker.middleware', ['web', 'auth']);

        return array_values($middleware);
    }

    /**
     * Get the disk image bytes are written to under the gated storage path.
     */
    public function storageDisk(): string
    {
        return (string) $this->get('storage.disk', 'local');
    }

    /**
     * Get the directory image bytes are written to under the gated storage path.
     */
    public function storagePath(): string
    {
        return trim((string) $this->get('storage.path', 'unsplash'), '/');
    }

    /**
     * Get the queue connection background work is dispatched on.
     */
    public function queueConnection(): ?string
    {
        $connection = $this->get('queue.connection');

        return filled($connection) ? (string) $connection : null;
    }

    /**
     * Get the queue background work is dispatched on.
     */
    public function queue(): ?string
    {
        $queue = $this->get('queue.queue');

        return filled($queue) ? (string) $queue : null;
    }

    /**
     * Determine if the package dispatches events.
     */
    public function eventsEnabled(): bool
    {
        return (bool) $this->get('events', true);
    }
}
