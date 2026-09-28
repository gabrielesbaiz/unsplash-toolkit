<?php

declare(strict_types=1);

use Gabrielesbaiz\UnsplashToolkit\Data\Photo;
use Gabrielesbaiz\UnsplashToolkit\Drivers\FakeUnsplash;
use Gabrielesbaiz\UnsplashToolkit\Facades\Unsplash;
use Gabrielesbaiz\UnsplashToolkit\Models\UnsplashAsset;

/**
 * C3: "you must send a request to the download endpoint returned under the
 * photo.links.download_location property."
 */
it('reports a download event when a photo is curated', function (): void {
    $fake = Unsplash::fake();

    $photo = Photo::fromResponse(FakeUnsplash::photoPayload());

    Unsplash::curate($photo, 'wallpapers');

    expect($fake->sentRequestTo('/download'))->toBeTrue();
});

it('uses the download_location URL from the API, keeping its ixid', function (): void {
    $fake = Unsplash::fake();

    $photo = Photo::fromResponse(FakeUnsplash::photoPayload());

    Unsplash::curate($photo);

    $tracked = collect($fake->requests)
        ->first(fn (array $request): bool => str_contains($request['endpoint'], '/download'));

    expect($tracked)->not->toBeNull()
        ->and($tracked['endpoint'])
        ->toBe($photo->downloadLocation)
        ->toContain('ixid=');
});

it('reports the event before the registry row is written', function (): void {
    $fake = Unsplash::fake();

    Unsplash::curate(Photo::fromResponse(FakeUnsplash::photoPayload()));

    // The ping is the very first request the curation makes.
    expect($fake->requests[0]['endpoint'])->toContain('/download');
});

it('stores the download location so it can be reported again later', function (): void {
    Unsplash::fake();

    $asset = Unsplash::curate(Photo::fromResponse(FakeUnsplash::photoPayload()));

    expect($asset->download_location)->toContain('/download')
        ->and(UnsplashAsset::query()->count())->toBe(1);
});
