<?php

declare(strict_types=1);

namespace Gabrielesbaiz\UnsplashToolkit\Data;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * Unsplash platform statistics.
 *
 * @implements Arrayable<string, mixed>
 */
final readonly class Stats implements Arrayable, JsonSerializable
{
    /**
     * Create a new statistics snapshot.
     *
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public ?int $photos = null,
        public ?int $downloads = null,
        public ?int $views = null,
        public ?int $photographers = null,
        public array $raw = [],
    ) {}

    /**
     * Create a statistics snapshot from an Unsplash API payload.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function fromResponse(array $payload): self
    {
        return new self(
            photos: isset($payload['photos']) ? (int) $payload['photos'] : null,
            downloads: isset($payload['downloads']) ? (int) $payload['downloads'] : null,
            views: isset($payload['views']) ? (int) $payload['views'] : null,
            photographers: isset($payload['photographers']) ? (int) $payload['photographers'] : null,
            raw: $payload,
        );
    }

    /**
     * Get the instance as an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'photos' => $this->photos,
            'downloads' => $this->downloads,
            'views' => $this->views,
            'photographers' => $this->photographers,
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
