<?php

declare(strict_types=1);

namespace Gabrielesbaiz\UnsplashToolkit\Facades;

use Gabrielesbaiz\UnsplashToolkit\Contracts\UnsplashClient;
use Gabrielesbaiz\UnsplashToolkit\Data\CollectionResource;
use Gabrielesbaiz\UnsplashToolkit\Data\Photo;
use Gabrielesbaiz\UnsplashToolkit\Data\PhotoCollection;
use Gabrielesbaiz\UnsplashToolkit\Data\RateLimit;
use Gabrielesbaiz\UnsplashToolkit\Data\Stats;
use Gabrielesbaiz\UnsplashToolkit\Drivers\FakeUnsplash;
use Gabrielesbaiz\UnsplashToolkit\Enums\Size;
use Gabrielesbaiz\UnsplashToolkit\Models\UnsplashAsset;
use Gabrielesbaiz\UnsplashToolkit\PendingRequest;
use Gabrielesbaiz\UnsplashToolkit\Support\Compliance;
use Gabrielesbaiz\UnsplashToolkit\Support\Config;
use Gabrielesbaiz\UnsplashToolkit\Support\Curator;
use Gabrielesbaiz\UnsplashToolkit\Support\Throttle;
use Gabrielesbaiz\UnsplashToolkit\UnsplashToolkit;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static PendingRequest search(string|null $term = null)
 * @method static PendingRequest searchCollections(string|null $term = null)
 * @method static PendingRequest searchUsers(string|null $term = null)
 * @method static PendingRequest photos()
 * @method static Photo photo(string $id)
 * @method static PhotoCollection photosByIds(array<int, string> $ids)
 * @method static PendingRequest random()
 * @method static PendingRequest photoStatistics(string $id)
 * @method static PendingRequest user(string $username)
 * @method static PendingRequest userPhotos(string $username)
 * @method static PendingRequest userLikes(string $username)
 * @method static PendingRequest userCollections(string $username)
 * @method static PendingRequest collections()
 * @method static CollectionResource collection(string $id)
 * @method static PendingRequest collectionPhotos(string $id)
 * @method static Stats stats()
 * @method static UnsplashAsset curate(Photo $photo, string|null $pool = null, Model|null $curatedBy = null)
 * @method static Collection<int, UnsplashAsset> curateMany(iterable<int, Photo> $photos, string|null $pool = null, Model|null $curatedBy = null)
 * @method static Collection<int, UnsplashAsset> curateCollection(string $collectionId, string|null $pool = null, int $perPage = 30)
 * @method static void trackDownload(Photo $photo)
 * @method static UnsplashAsset refresh(UnsplashAsset $asset)
 * @method static UnsplashAsset|null fromPool(string|null $pool = null)
 * @method static UnsplashAsset import(Photo $photo, Size $size = Size::Regular, string|null $pool = null)
 * @method static RateLimit|null rateLimit()
 * @method static Throttle throttle()
 * @method static Compliance compliance()
 * @method static Config config()
 * @method static PendingRequest request(string $endpoint, string $shape = 'photos')
 *
 * @see UnsplashToolkit
 */
class Unsplash extends Facade
{
    /**
     * Replace the API client with an in-memory fake.
     */
    public static function fake(?FakeUnsplash $fake = null): FakeUnsplash
    {
        $fake ??= new FakeUnsplash;

        /** @var Application $app */
        $app = static::getFacadeApplication();

        $app->instance(UnsplashClient::class, $fake);
        $app->forgetInstance(UnsplashToolkit::class);
        $app->forgetInstance(Curator::class);

        static::clearResolvedInstance(static::getFacadeAccessor());

        return $fake;
    }

    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return UnsplashToolkit::class;
    }
}
