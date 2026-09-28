<?php

declare(strict_types=1);

namespace Gabrielesbaiz\UnsplashToolkit\Data;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * An Unsplash collection: a curated set of photos maintained on unsplash.com.
 *
 * Editors who already keep a collection there can promote it straight into a
 * local pool, so the curation work is not duplicated.
 *
 * @implements Arrayable<string, mixed>
 */
final readonly class CollectionResource implements Arrayable, JsonSerializable
{
    /**
     * Create a new collection resource.
     */
    public function __construct(
        public string $id,
        public string $title,
        public ?string $description = null,
        public int $totalPhotos = 0,
        public ?Author $curator = null,
        public ?Photo $coverPhoto = null,
        public ?string $htmlLink = null,
    ) {}

    /**
     * Create a collection resource from an Unsplash API payload.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function fromResponse(array $payload): self
    {
        /** @var array<string, mixed> $links */
        $links = is_array($payload['links'] ?? null) ? $payload['links'] : [];

        /** @var array<string, mixed>|null $cover */
        $cover = is_array($payload['cover_photo'] ?? null) ? $payload['cover_photo'] : null;

        /** @var array<string, mixed>|null $user */
        $user = is_array($payload['user'] ?? null) ? $payload['user'] : null;

        return new self(
            id: (string) ($payload['id'] ?? ''),
            title: (string) ($payload['title'] ?? ''),
            description: isset($payload['description']) ? (string) $payload['description'] : null,
            totalPhotos: (int) ($payload['total_photos'] ?? 0),
            curator: $user !== null ? Author::fromResponse($user) : null,
            coverPhoto: $cover !== null ? Photo::fromResponse($cover) : null,
            htmlLink: isset($links['html']) ? (string) $links['html'] : null,
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
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'total_photos' => $this->totalPhotos,
            'curator' => $this->curator?->toArray(),
            'cover_photo' => $this->coverPhoto?->toArray(),
            'html_link' => $this->htmlLink,
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
