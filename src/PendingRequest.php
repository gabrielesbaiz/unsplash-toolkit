<?php

declare(strict_types=1);

namespace Gabrielesbaiz\UnsplashToolkit;

use Gabrielesbaiz\UnsplashToolkit\Contracts\UnsplashClient;
use Gabrielesbaiz\UnsplashToolkit\Data\CollectionResource;
use Gabrielesbaiz\UnsplashToolkit\Data\Photo;
use Gabrielesbaiz\UnsplashToolkit\Data\PhotoCollection;
use Gabrielesbaiz\UnsplashToolkit\Data\SearchResult;
use Gabrielesbaiz\UnsplashToolkit\Enums\Color;
use Gabrielesbaiz\UnsplashToolkit\Enums\ContentFilter;
use Gabrielesbaiz\UnsplashToolkit\Enums\OrderBy;
use Gabrielesbaiz\UnsplashToolkit\Enums\Orientation;
use Illuminate\Support\Collection;

/**
 * An immutable, fluent description of one Unsplash request.
 *
 * Every modifier returns a new instance. The previous design mutated shared
 * state on a facade-cached object, so parameters from one call leaked into the
 * next; cloning makes that impossible rather than merely unlikely.
 *
 * @phpstan-type ResultShape 'photos'|'search'|'photo'|'collections'|'collection'
 */
final readonly class PendingRequest
{
    /**
     * Create a new pending request.
     *
     * @param  array<string, mixed>  $query
     */
    public function __construct(
        private UnsplashClient $client,
        private string $endpoint,
        private string $shape = 'photos',
        private array $query = [],
    ) {}

    /**
     * Set the page of results to retrieve.
     */
    public function page(int $page): self
    {
        return $this->withQuery('page', $page);
    }

    /**
     * Set the number of items per page.
     */
    public function perPage(int $perPage): self
    {
        return $this->withQuery('per_page', $perPage);
    }

    /**
     * Set the number of photos to return.
     */
    public function count(int $count): self
    {
        return $this->withQuery('count', $count);
    }

    /**
     * Set the search term.
     */
    public function term(string $term): self
    {
        return $this->withQuery('query', $term);
    }

    /**
     * Set how results are sorted.
     */
    public function orderBy(OrderBy|string $orderBy): self
    {
        return $this->withQuery('order_by', $orderBy instanceof OrderBy ? $orderBy->value : $orderBy);
    }

    /**
     * Filter results by orientation.
     */
    public function orientation(Orientation|string $orientation): self
    {
        return $this->withQuery('orientation', $orientation instanceof Orientation ? $orientation->value : $orientation);
    }

    /**
     * Filter results by colour.
     */
    public function color(Color|string $color): self
    {
        return $this->withQuery('color', $color instanceof Color ? $color->value : $color);
    }

    /**
     * Set how strictly results are filtered for sensitive content.
     */
    public function contentFilter(ContentFilter|string $filter): self
    {
        return $this->withQuery('content_filter', $filter instanceof ContentFilter ? $filter->value : $filter);
    }

    /**
     * Restrict the selection to the given collection ids.
     *
     * @param  array<int, string|int>|string  $collections
     */
    public function collections(array|string $collections): self
    {
        return $this->withQuery('collections', is_array($collections) ? implode(',', $collections) : $collections);
    }

    /**
     * Restrict the selection to a single photographer.
     */
    public function username(string $username): self
    {
        return $this->withQuery('username', $username);
    }

    /**
     * Restrict the selection to featured photos.
     */
    public function featured(bool $featured = true): self
    {
        return $this->withQuery('featured', $featured ? 'true' : 'false');
    }

    /**
     * Set an arbitrary query parameter.
     */
    public function withQuery(string $key, mixed $value): self
    {
        return new self($this->client, $this->endpoint, $this->shape, [...$this->query, $key => $value]);
    }

    /**
     * Get the query parameters this request will send.
     *
     * @return array<string, mixed>
     */
    public function query(): array
    {
        return $this->query;
    }

    /**
     * Get the endpoint this request will call.
     */
    public function endpoint(): string
    {
        return $this->endpoint;
    }

    /**
     * Execute the request and return the shaped result.
     *
     * Endpoints with no dedicated shape, such as users and statistics, return
     * the decoded payload as given.
     *
     * @return PhotoCollection|SearchResult|Photo|CollectionResource|Collection<int, CollectionResource>|array<mixed>
     */
    public function get(): PhotoCollection|SearchResult|Photo|CollectionResource|Collection|array
    {
        return match ($this->shape) {
            'search' => SearchResult::fromResponse($this->send(), (int) ($this->query['page'] ?? 1)),
            'photo' => Photo::fromResponse($this->send()),
            'collection' => CollectionResource::fromResponse($this->send()),
            'collections' => $this->collectionsFromResponse($this->send()),
            'raw' => $this->send(),
            default => $this->photosFromResponse($this->send()),
        };
    }

    /**
     * Execute the request and return the first photo, if there is one.
     */
    public function first(): ?Photo
    {
        $result = $this->get();

        return match (true) {
            $result instanceof Photo => $result,
            $result instanceof SearchResult => $result->photos->first(),
            $result instanceof PhotoCollection => $result->first(),
            default => null,
        };
    }

    /**
     * Execute the request and return the decoded payload untouched.
     *
     * @return array<mixed>
     */
    public function toArray(): array
    {
        return $this->send();
    }

    /**
     * Send the request.
     *
     * @return array<mixed>
     */
    private function send(): array
    {
        return $this->client->get($this->endpoint, $this->query);
    }

    /**
     * Shape a payload that may be a single photo or a list of them.
     *
     * @param  array<mixed>  $payload
     */
    private function photosFromResponse(array $payload): PhotoCollection
    {
        if (isset($payload['id'])) {
            /** @var array<string, mixed> $payload */
            return new PhotoCollection([Photo::fromResponse($payload)]);
        }

        /** @var array<int, array<string, mixed>> $payload */
        return PhotoCollection::fromResponse($payload);
    }

    /**
     * Shape a payload of Unsplash collections.
     *
     * @param  array<mixed>  $payload
     * @return Collection<int, CollectionResource>
     */
    private function collectionsFromResponse(array $payload): Collection
    {
        /** @var array<int, array<string, mixed>> $items */
        $items = isset($payload['results']) && is_array($payload['results']) ? $payload['results'] : $payload;

        return new Collection(array_map(CollectionResource::fromResponse(...), array_values($items)));
    }
}
