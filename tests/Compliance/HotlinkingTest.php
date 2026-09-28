<?php

declare(strict_types=1);

use Gabrielesbaiz\UnsplashToolkit\Data\Photo;
use Gabrielesbaiz\UnsplashToolkit\Drivers\FakeUnsplash;
use Gabrielesbaiz\UnsplashToolkit\Enums\Size;
use Gabrielesbaiz\UnsplashToolkit\Exceptions\HotlinkingRequiredException;
use Gabrielesbaiz\UnsplashToolkit\Facades\Unsplash;

/**
 * C1: "All API uses must use the hotlinked image URLs returned by the API under
 * the photo.urls properties."
 *
 * C2: storing copies is gated behind written permission from Unsplash.
 */
it('returns the hotlinked URL Unsplash supplied, unmodified', function (): void {
    $photo = Photo::fromResponse(FakeUnsplash::photoPayload());

    expect($photo->url(Size::Regular))
        ->toBe($photo->urls['regular'])
        ->toStartWith('https://images.unsplash.com/');
});

it('resizes through Unsplash rather than by copying the file', function (): void {
    $photo = Photo::fromResponse(FakeUnsplash::photoPayload());

    $url = $photo->url(width: 1920, quality: 70);

    expect($url)
        ->toStartWith('https://images.unsplash.com/')
        ->toContain('w=1920')
        ->toContain('q=70');
});

it('refuses to download image bytes by default', function (): void {
    Unsplash::fake();

    $photo = Photo::fromResponse(FakeUnsplash::photoPayload());

    expect(config('unsplash-toolkit.compliance.allow_local_storage'))->toBeFalse();

    Unsplash::import($photo);
})->throws(HotlinkingRequiredException::class, 'hotlinked image URLs');

it('still refuses to download when permission is claimed but not recorded', function (): void {
    Unsplash::fake();

    config()->set('unsplash-toolkit.compliance.allow_local_storage', true);
    config()->set('unsplash-toolkit.compliance.storage_permission_reference', null);

    Unsplash::import(Photo::fromResponse(FakeUnsplash::photoPayload()));
})->throws(HotlinkingRequiredException::class, 'storage_permission_reference');
