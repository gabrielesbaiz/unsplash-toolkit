<?php

declare(strict_types=1);

namespace Gabrielesbaiz\UnsplashToolkit\Data;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * The hourly budget Unsplash reports on every response.
 *
 * @implements Arrayable<string, mixed>
 */
final readonly class RateLimit implements Arrayable, JsonSerializable
{
    /**
     * Create a new rate limit snapshot.
     */
    public function __construct(
        public ?int $limit = null,
        public ?int $remaining = null,
    ) {}

    /**
     * Create a snapshot from Unsplash response headers.
     *
     * @param  array<string, array<int, string>|string>  $headers
     */
    public static function fromHeaders(array $headers): self
    {
        $read = static function (string $name) use ($headers): ?int {
            foreach ($headers as $key => $value) {
                if (strcasecmp($key, $name) !== 0) {
                    continue;
                }

                $value = is_array($value) ? ($value[0] ?? null) : $value;

                return $value === null || $value === '' ? null : (int) $value;
            }

            return null;
        };

        return new self(
            limit: $read('X-Ratelimit-Limit'),
            remaining: $read('X-Ratelimit-Remaining'),
        );
    }

    /**
     * Get the number of requests spent in the current window.
     */
    public function used(): ?int
    {
        if ($this->limit === null || $this->remaining === null) {
            return null;
        }

        return max(0, $this->limit - $this->remaining);
    }

    /**
     * Determine if the reported budget is exhausted.
     */
    public function isExhausted(): bool
    {
        return $this->remaining !== null && $this->remaining <= 0;
    }

    /**
     * Determine if the application is running against the demo tier.
     */
    public function isDemo(): bool
    {
        return $this->limit !== null && $this->limit <= 50;
    }

    /**
     * Get the instance as an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'limit' => $this->limit,
            'remaining' => $this->remaining,
            'used' => $this->used(),
            'demo' => $this->isDemo(),
        ];
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
