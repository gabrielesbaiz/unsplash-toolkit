<?php

declare(strict_types=1);

namespace Gabrielesbaiz\UnsplashToolkit\Data;

use Illuminate\Contracts\Support\Arrayable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * A single page of search results.
 *
 * @implements Arrayable<string, mixed>
 * @implements IteratorAggregate<int, Photo>
 */
final readonly class SearchResult implements Arrayable, IteratorAggregate, JsonSerializable
{
    /**
     * Create a new search result.
     */
    public function __construct(
        public PhotoCollection $photos,
        public int $total = 0,
        public int $totalPages = 0,
        public int $page = 1,
    ) {}

    /**
     * Create a search result from an Unsplash API payload.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function fromResponse(array $payload, int $page = 1): self
    {
        /** @var array<int, array<string, mixed>> $results */
        $results = is_array($payload['results'] ?? null) ? $payload['results'] : [];

        return new self(
            photos: PhotoCollection::fromResponse($results),
            total: (int) ($payload['total'] ?? count($results)),
            totalPages: (int) ($payload['total_pages'] ?? 1),
            page: $page,
        );
    }

    /**
     * Determine if another page of results is available.
     */
    public function hasMorePages(): bool
    {
        return $this->page < $this->totalPages;
    }

    /**
     * Get an iterator for the photos.
     *
     * @return Traversable<int, Photo>
     */
    public function getIterator(): Traversable
    {
        return $this->photos->getIterator();
    }

    /**
     * Get the instance as an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'total' => $this->total,
            'total_pages' => $this->totalPages,
            'page' => $this->page,
            'results' => $this->photos->toArray(),
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
