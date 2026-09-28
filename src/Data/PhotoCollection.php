<?php

declare(strict_types=1);

namespace Gabrielesbaiz\UnsplashToolkit\Data;

use Illuminate\Support\Collection;

/**
 * A collection of photos.
 *
 * @extends Collection<int, Photo>
 */
final class PhotoCollection extends Collection
{
    /**
     * Create a photo collection from an Unsplash API payload.
     *
     * @param  array<int, array<string, mixed>>  $payload
     */
    public static function fromResponse(array $payload): self
    {
        return new self(array_map(Photo::fromResponse(...), array_values($payload)));
    }

    /**
     * Get the ids of every photo in the collection.
     *
     * @return array<int, string>
     */
    public function ids(): array
    {
        return array_values(array_map(static fn (Photo $photo): string => $photo->id, $this->all()));
    }
}
