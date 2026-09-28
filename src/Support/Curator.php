<?php

declare(strict_types=1);

namespace Gabrielesbaiz\UnsplashToolkit\Support;

use Gabrielesbaiz\UnsplashToolkit\Contracts\UnsplashClient;
use Gabrielesbaiz\UnsplashToolkit\Data\Photo;
use Gabrielesbaiz\UnsplashToolkit\Enums\AssetStatus;
use Gabrielesbaiz\UnsplashToolkit\Events\PhotoCurated;
use Gabrielesbaiz\UnsplashToolkit\Events\PhotoUnavailable;
use Gabrielesbaiz\UnsplashToolkit\Exceptions\PhotoNotFoundException;
use Gabrielesbaiz\UnsplashToolkit\Models\UnsplashAsset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Records which photos an application has approved for use.
 *
 * Curating is the moment a person chooses a photo, which is exactly the event
 * the API Guidelines require to be reported: every curation pings the URL under
 * photo.links.download_location before the registry row is written.
 */
final readonly class Curator
{
    /**
     * Create a new curator.
     */
    public function __construct(
        private Config $config,
        private UnsplashClient $client,
    ) {}

    /**
     * Approve a photo for use and add it to a pool.
     */
    public function curate(Photo $photo, ?string $pool = null, ?Model $curatedBy = null): UnsplashAsset
    {
        $this->trackDownload($photo);

        $asset = UnsplashAsset::query()->firstOrNew(['unsplash_id' => $photo->id]);

        $asset->fill(UnsplashAsset::attributesFrom($photo));

        $asset->pool = $pool ?? $this->config->defaultPool();
        $asset->status = AssetStatus::Active;
        $asset->curated_at = $asset->curated_at ?? now();
        $asset->last_verified_at = now();

        if ($curatedBy instanceof Model) {
            $asset->curatedBy()->associate($curatedBy);
        }

        $asset->save();

        if ($this->config->eventsEnabled()) {
            PhotoCurated::dispatch($asset);
        }

        return $asset;
    }

    /**
     * Approve several explicitly chosen photos.
     *
     * This takes photo objects a person selected. There is deliberately no
     * "give me N photos and save them all" entry point: the guidelines ask for
     * non-automated, authentic use rather than bulk harvesting.
     *
     * @param  iterable<int, Photo>  $photos
     * @return Collection<int, UnsplashAsset>
     */
    public function curateMany(iterable $photos, ?string $pool = null, ?Model $curatedBy = null): Collection
    {
        $assets = new Collection;

        foreach ($photos as $photo) {
            $assets->push($this->curate($photo, $pool, $curatedBy));
        }

        return $assets;
    }

    /**
     * Report a download event for a photo.
     *
     * The URL comes from the API response rather than being built by hand: it
     * carries the ixid that attributes the event to this application.
     */
    public function trackDownload(Photo $photo): void
    {
        if ($photo->downloadLocation === '') {
            return;
        }

        $this->client->getAbsolute($photo->downloadLocation);
    }

    /**
     * Re-fetch a curated photo's metadata from Unsplash.
     *
     * Keeps attribution accurate when a photographer renames, and detects a
     * photo that has been removed.
     */
    public function refresh(UnsplashAsset $asset): UnsplashAsset
    {
        try {
            $payload = $this->client->get("photos/{$asset->unsplash_id}");
        } catch (PhotoNotFoundException $exception) {
            return $this->markUnavailable($asset, $exception->getMessage());
        }

        /** @var array<string, mixed> $payload */
        $photo = Photo::fromResponse($payload);

        $asset->fill(UnsplashAsset::attributesFrom($photo));
        $asset->status = AssetStatus::Active;
        $asset->last_verified_at = now();
        $asset->save();

        return $asset;
    }

    /**
     * Rebuild a registry row that only holds an Unsplash id.
     *
     * This is the upgrade path from v1, where a row recorded a downloaded file
     * rather than the photo's URLs.
     */
    public function backfill(UnsplashAsset $asset, ?string $pool = null): UnsplashAsset
    {
        if ($pool !== null) {
            $asset->pool = $pool;
        }

        return $this->refresh($asset);
    }

    /**
     * Mark a photo as no longer servable.
     */
    public function markUnavailable(UnsplashAsset $asset, string $reason = ''): UnsplashAsset
    {
        $asset->status = AssetStatus::Unavailable;
        $asset->last_verified_at = now();
        $asset->save();

        if ($this->config->eventsEnabled()) {
            PhotoUnavailable::dispatch($asset, $reason);
        }

        return $asset;
    }
}
