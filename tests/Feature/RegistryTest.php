<?php

declare(strict_types=1);

use Gabrielesbaiz\UnsplashToolkit\Data\Photo;
use Gabrielesbaiz\UnsplashToolkit\Drivers\FakeUnsplash;
use Gabrielesbaiz\UnsplashToolkit\Enums\AssetStatus;
use Gabrielesbaiz\UnsplashToolkit\Events\PhotoCurated;
use Gabrielesbaiz\UnsplashToolkit\Events\PhotoUnavailable;
use Gabrielesbaiz\UnsplashToolkit\Events\PoolDepleted;
use Gabrielesbaiz\UnsplashToolkit\Exceptions\PoolDepletedException;
use Gabrielesbaiz\UnsplashToolkit\Facades\Unsplash;
use Gabrielesbaiz\UnsplashToolkit\Models\UnsplashAsset;
use Gabrielesbaiz\UnsplashToolkit\Support\Curator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

it('selects from a pool without calling the Unsplash API', function (): void {
    $fake = Unsplash::fake();

    UnsplashAsset::factory()->count(10)->pool('login-backgrounds')->create();

    $asset = UnsplashAsset::random('login-backgrounds');

    expect($asset)->not->toBeNull()
        ->and($asset->pool)->toBe('login-backgrounds')
        // The whole point of the registry: rendering costs no API budget.
        ->and($fake->requestCount())->toBe(0);
});

it('keeps pools separate', function (): void {
    UnsplashAsset::factory()->count(5)->pool('hero')->create();
    UnsplashAsset::factory()->count(5)->pool('login-backgrounds')->create();

    foreach (range(1, 10) as $ignored) {
        expect(UnsplashAsset::random('hero')?->pool)->toBe('hero');
    }
});

it('never selects a photo that is no longer available', function (): void {
    UnsplashAsset::factory()->count(5)->pool('hero')->unavailable()->create();
    $active = UnsplashAsset::factory()->pool('hero')->create();

    foreach (range(1, 10) as $ignored) {
        expect(UnsplashAsset::random('hero')?->getKey())->toBe($active->getKey());
    }
});

it('caches the selected photo across repeated renders', function (): void {
    UnsplashAsset::factory()->count(10)->pool('hero')->create();

    $first = UnsplashAsset::cachedRandom('hero');

    DB::enableQueryLog();

    $second = UnsplashAsset::cachedRandom('hero');

    expect($second?->getKey())->toBe($first?->getKey())
        ->and(DB::getQueryLog())->toHaveCount(1);
});

it('drops a cached selection that has gone unavailable', function (): void {
    $asset = UnsplashAsset::factory()->pool('hero')->create();

    expect(UnsplashAsset::cachedRandom('hero')?->getKey())->toBe($asset->getKey());

    $asset->update(['status' => AssetStatus::Unavailable]);

    expect(UnsplashAsset::cachedRandom('hero'))->toBeNull();
});

it('returns null rather than failing when a pool is empty', function (): void {
    expect(UnsplashAsset::random('nothing-here'))->toBeNull()
        ->and(UnsplashAsset::cachedRandom('nothing-here'))->toBeNull();
});

it('announces a pool that is running dry', function (): void {
    Event::fake([PoolDepleted::class]);

    config()->set('unsplash-toolkit.pools.min_size', 5);

    UnsplashAsset::factory()->count(2)->pool('hero')->create();

    UnsplashAsset::random('hero');

    Event::assertDispatched(PoolDepleted::class, fn (PoolDepleted $event): bool => $event->pool === 'hero'
        && $event->remaining === 2
        && $event->minimum === 5);
});

it('records a curated photo without storing any bytes', function (): void {
    Event::fake([PhotoCurated::class]);
    Unsplash::fake();

    $asset = Unsplash::curate(Photo::fromResponse(FakeUnsplash::photoPayload()), 'hero');

    expect($asset->pool)->toBe('hero')
        ->and($asset->status)->toBe(AssetStatus::Active)
        ->and($asset->urls)->toHaveKey('regular')
        ->and($asset->disk)->toBeNull()
        ->and($asset->path)->toBeNull();

    Event::assertDispatched(PhotoCurated::class);
});

it('does not duplicate a photo that is curated twice', function (): void {
    Unsplash::fake();

    $photo = Photo::fromResponse(FakeUnsplash::photoPayload());

    Unsplash::curate($photo, 'hero');
    Unsplash::curate($photo, 'hero');

    expect(UnsplashAsset::query()->count())->toBe(1);
});

it('marks a removed photo unavailable instead of serving a dead link', function (): void {
    Event::fake([PhotoUnavailable::class]);

    $fake = Unsplash::fake();
    $asset = UnsplashAsset::factory()->pool('hero')->create();

    $fake->missing($asset->unsplash_id);

    app(Curator::class)->refresh($asset);

    expect($asset->fresh()?->status)->toBe(AssetStatus::Unavailable);

    Event::assertDispatched(PhotoUnavailable::class);
});

it('rebuilds a v1 row that only holds an Unsplash id', function (): void {
    Unsplash::fake();

    $asset = UnsplashAsset::factory()->create([
        'unsplash_id' => 'Dwu85P9SOIk',
        'urls' => [],
        'download_location' => null,
        'pool' => 'default',
    ]);

    app(Curator::class)->backfill($asset, 'login-backgrounds');

    $asset->refresh();

    expect($asset->pool)->toBe('login-backgrounds')
        ->and($asset->urls)->toHaveKey('regular')
        ->and($asset->download_location)->toContain('/download')
        ->and($asset->status)->toBe(AssetStatus::Active);
});

it('fails loudly when a pool is empty and a photo is required', function (): void {
    UnsplashAsset::randomOrFail('nothing-here');
})->throws(PoolDepletedException::class);

it('returns a photo from randomOrFail when the pool has one', function (): void {
    $asset = UnsplashAsset::factory()->pool('hero')->create();

    expect(UnsplashAsset::randomOrFail('hero')->getKey())->toBe($asset->getKey());
});
