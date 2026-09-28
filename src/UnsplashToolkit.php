<?php

declare(strict_types=1);

namespace Gabrielesbaiz\UnsplashToolkit;

use Gabrielesbaiz\UnsplashToolkit\Contracts\UnsplashClient;
use Gabrielesbaiz\UnsplashToolkit\Data\CollectionResource;
use Gabrielesbaiz\UnsplashToolkit\Data\Photo;
use Gabrielesbaiz\UnsplashToolkit\Data\PhotoCollection;
use Gabrielesbaiz\UnsplashToolkit\Data\RateLimit;
use Gabrielesbaiz\UnsplashToolkit\Data\Stats;
use Gabrielesbaiz\UnsplashToolkit\Enums\Size;
use Gabrielesbaiz\UnsplashToolkit\Models\UnsplashAsset;
use Gabrielesbaiz\UnsplashToolkit\Support\Compliance;
use Gabrielesbaiz\UnsplashToolkit\Support\Config;
use Gabrielesbaiz\UnsplashToolkit\Support\Curator;
use Gabrielesbaiz\UnsplashToolkit\Support\Downloader;
use Gabrielesbaiz\UnsplashToolkit\Support\Throttle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * The entry point of the package.
 *
 * Browsing returns hotlinked photos, curating records which of them an
 * application has approved, and pools are then read from the local database so
 * rendering a page costs no Unsplash API requests.
 */
final readonly class UnsplashToolkit
{
    /**
     * Create a new toolkit instance.
     */
    public function __construct(
        private UnsplashClient $client,
        private Config $config,
        private Compliance $compliance,
        private Curator $curator,
        private Throttle $throttle,
    ) {}

    /**
     * Search photos.
     *
     * @see https://unsplash.com/documentation#search-photos
     */
    public function search(?string $term = null): PendingRequest
    {
        $request = $this->request('search/photos', 'search');

        return $term === null ? $request : $request->term($term);
    }

    /**
     * Search collections.
     */
    public function searchCollections(?string $term = null): PendingRequest
    {
        $request = $this->request('search/collections', 'collections');

        return $term === null ? $request : $request->term($term);
    }

    /**
     * Search users.
     */
    public function searchUsers(?string $term = null): PendingRequest
    {
        $request = $this->request('search/users', 'raw');

        return $term === null ? $request : $request->term($term);
    }

    /**
     * List photos.
     */
    public function photos(): PendingRequest
    {
        return $this->request('photos');
    }

    /**
     * Get a single photo.
     */
    public function photo(string $id): Photo
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->client->get("photos/{$id}");

        return Photo::fromResponse($payload);
    }

    /**
     * Get several photos concurrently.
     *
     * Fetching by id is the one place a batch is unavoidable, so the requests
     * are pooled rather than issued one after another.
     *
     * @param  array<int, string>  $ids
     */
    public function photosByIds(array $ids): PhotoCollection
    {
        $requests = [];

        foreach (array_unique($ids) as $id) {
            $requests[$id] = ['endpoint' => "photos/{$id}"];
        }

        $photos = new PhotoCollection;

        foreach ($this->client->pool($requests) as $payload) {
            if (is_array($payload) && isset($payload['id'])) {
                /** @var array<string, mixed> $payload */
                $photos->push(Photo::fromResponse($payload));
            }
        }

        return $photos;
    }

    /**
     * Get random photos.
     */
    public function random(): PendingRequest
    {
        return $this->request('photos/random');
    }

    /**
     * Get a photo's statistics.
     */
    public function photoStatistics(string $id): PendingRequest
    {
        return $this->request("photos/{$id}/statistics", 'raw');
    }

    /**
     * Get a user's profile.
     */
    public function user(string $username): PendingRequest
    {
        return $this->request("users/{$username}", 'raw');
    }

    /**
     * Get a user's photos.
     */
    public function userPhotos(string $username): PendingRequest
    {
        return $this->request("users/{$username}/photos");
    }

    /**
     * Get a user's likes.
     */
    public function userLikes(string $username): PendingRequest
    {
        return $this->request("users/{$username}/likes");
    }

    /**
     * Get a user's collections.
     */
    public function userCollections(string $username): PendingRequest
    {
        return $this->request("users/{$username}/collections", 'collections');
    }

    /**
     * List collections.
     */
    public function collections(): PendingRequest
    {
        return $this->request('collections', 'collections');
    }

    /**
     * Get a single collection.
     */
    public function collection(string $id): CollectionResource
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->client->get("collections/{$id}");

        return CollectionResource::fromResponse($payload);
    }

    /**
     * Get the photos in a collection.
     */
    public function collectionPhotos(string $id): PendingRequest
    {
        return $this->request("collections/{$id}/photos");
    }

    /**
     * Get Unsplash's total statistics.
     */
    public function stats(): Stats
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->client->get('stats/total');

        return Stats::fromResponse($payload);
    }

    /**
     * Approve a photo for use and add it to a pool.
     *
     * Reports the download event Unsplash requires, then records the photo's
     * metadata and hotlinked URLs. No image bytes are transferred.
     */
    public function curate(Photo $photo, ?string $pool = null, ?Model $curatedBy = null): UnsplashAsset
    {
        return $this->curator->curate($photo, $pool, $curatedBy);
    }

    /**
     * Approve several explicitly chosen photos.
     *
     * @param  iterable<int, Photo>  $photos
     * @return Collection<int, UnsplashAsset>
     */
    public function curateMany(iterable $photos, ?string $pool = null, ?Model $curatedBy = null): Collection
    {
        return $this->curator->curateMany($photos, $pool, $curatedBy);
    }

    /**
     * Approve every photo in an Unsplash collection.
     *
     * Editors who already curate a collection on unsplash.com can promote it
     * straight into a local pool.
     *
     * @return Collection<int, UnsplashAsset>
     */
    public function curateCollection(string $collectionId, ?string $pool = null, int $perPage = 30): Collection
    {
        $photos = $this->collectionPhotos($collectionId)->perPage($perPage)->get();

        /** @var PhotoCollection $photos */
        return $this->curator->curateMany($photos, $pool);
    }

    /**
     * Report a download event for a photo.
     */
    public function trackDownload(Photo $photo): void
    {
        $this->curator->trackDownload($photo);
    }

    /**
     * Re-fetch a curated photo's metadata.
     */
    public function refresh(UnsplashAsset $asset): UnsplashAsset
    {
        return $this->curator->refresh($asset);
    }

    /**
     * Get one random approved photo from a pool.
     */
    public function fromPool(?string $pool = null): ?UnsplashAsset
    {
        return UnsplashAsset::cachedRandom($pool);
    }

    /**
     * Download a photo's bytes to a local disk.
     *
     * Hotlinking is the compliant default, so this throws unless Unsplash has
     * granted written permission and it has been recorded in config.
     */
    public function import(Photo $photo, Size $size = Size::Regular, ?string $pool = null): UnsplashAsset
    {
        $this->compliance->assertLocalStorageAllowed();

        $asset = $this->curator->curate($photo, $pool);

        return app(Downloader::class)->store($photo, $asset, $size);
    }

    /**
     * Get the rate limit Unsplash reported on the most recent response.
     */
    public function rateLimit(): ?RateLimit
    {
        return $this->client->rateLimit();
    }

    /**
     * Get the local throttle.
     */
    public function throttle(): Throttle
    {
        return $this->throttle;
    }

    /**
     * Get the compliance guard.
     */
    public function compliance(): Compliance
    {
        return $this->compliance;
    }

    /**
     * Get the package configuration.
     */
    public function config(): Config
    {
        return $this->config;
    }

    /**
     * Get the underlying API client.
     */
    public function client(): UnsplashClient
    {
        return $this->client;
    }

    /**
     * Start a request against an arbitrary endpoint.
     */
    public function request(string $endpoint, string $shape = 'photos'): PendingRequest
    {
        return new PendingRequest($this->client, $endpoint, $shape);
    }
}
