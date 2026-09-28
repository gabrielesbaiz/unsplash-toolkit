<?php

declare(strict_types=1);

use Gabrielesbaiz\UnsplashToolkit\Data\Photo;
use Gabrielesbaiz\UnsplashToolkit\Drivers\FakeUnsplash;
use Gabrielesbaiz\UnsplashToolkit\Enums\Size;
use Gabrielesbaiz\UnsplashToolkit\Support\ImageUrl;

/**
 * C4: "All resizing and manipulations of image URLs must keep this parameter as
 * it allows for your application to report photo views."
 */
it('keeps the ixid when a URL is resized', function (): void {
    $photo = Photo::fromResponse(FakeUnsplash::photoPayload());

    expect($photo->url(width: 1280, height: 720, quality: 60, dpr: 2))
        ->toContain('ixid=M3wxMjA3fDB8MXxhbGx8fHx8fHx8fHwx');
});

it('keeps the ixid in every srcset candidate', function (): void {
    $photo = Photo::fromResponse(FakeUnsplash::photoPayload());

    $candidates = explode(', ', $photo->srcset([640, 1280, 1920]));

    expect($candidates)->toHaveCount(3);

    foreach ($candidates as $candidate) {
        expect($candidate)->toContain('ixid=');
    }
});

it('keeps the ixid across every size', function (): void {
    $photo = Photo::fromResponse(FakeUnsplash::photoPayload());
    $builder = app(ImageUrl::class);

    foreach (Size::cases() as $size) {
        expect($builder->hasTrackingParameter($photo->url($size, width: 800)))->toBeTrue();
    }
});

it('refuses to let a caller overwrite the ixid', function (): void {
    $builder = app(ImageUrl::class);

    $url = $builder->build(
        'https://images.unsplash.com/photo-x?ixid=original&ixlib=rb-4.0.3',
        ['ixid' => 'tampered', 'w' => 400],
    );

    expect($url)
        ->toContain('ixid=original')
        ->not->toContain('tampered');
});

it('preserves the ixlib parameter too', function (): void {
    $photo = Photo::fromResponse(FakeUnsplash::photoPayload());

    expect($photo->url(width: 500))->toContain('ixlib=rb-4.0.3');
});
