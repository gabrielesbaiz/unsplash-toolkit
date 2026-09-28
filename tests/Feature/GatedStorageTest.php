<?php

declare(strict_types=1);

use Gabrielesbaiz\UnsplashToolkit\Data\Photo;
use Gabrielesbaiz\UnsplashToolkit\Drivers\FakeUnsplash;
use Gabrielesbaiz\UnsplashToolkit\Enums\Size;
use Gabrielesbaiz\UnsplashToolkit\Exceptions\DownloadFailedException;
use Gabrielesbaiz\UnsplashToolkit\Facades\Unsplash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * The gated path, exercised only with a recorded permission from Unsplash.
 */
beforeEach(function (): void {
    config()->set('unsplash-toolkit.compliance.allow_local_storage', true);
    config()->set('unsplash-toolkit.compliance.storage_permission_reference', 'UNSPLASH-TICKET-1234');
    config()->set('unsplash-toolkit.storage.disk', 'local');

    Storage::fake('local');
});

it('streams the image to disk and records it on the registry entry', function (): void {
    Unsplash::fake();

    Http::fake(['images.unsplash.com/*' => Http::response('binary-image-bytes', 200, ['Content-Type' => 'image/jpeg'])]);

    $photo = Photo::fromResponse(FakeUnsplash::photoPayload());

    $asset = Unsplash::import($photo, Size::Regular, 'hero');

    expect($asset->disk)->toBe('local')
        ->and($asset->path)->toEndWith('.jpg')
        ->and($asset->mime)->toBe('image/jpeg');

    Storage::disk('local')->assertExists($asset->path);
});

it('writes the same file name for the same photo and size', function (): void {
    Unsplash::fake();

    Http::fake(['images.unsplash.com/*' => Http::response('bytes', 200, ['Content-Type' => 'image/jpeg'])]);

    $photo = Photo::fromResponse(FakeUnsplash::photoPayload());

    $first = Unsplash::import($photo, Size::Regular);
    $second = Unsplash::import($photo, Size::Regular);

    // Content addressed, so importing twice does not probe the disk for a free name.
    expect($first->path)->toBe($second->path);
});

it('uses the real extension for the content type served', function (): void {
    Unsplash::fake();

    Http::fake(['images.unsplash.com/*' => Http::response('bytes', 200, ['Content-Type' => 'image/webp'])]);

    $asset = Unsplash::import(Photo::fromResponse(FakeUnsplash::photoPayload()));

    expect($asset->path)->toEndWith('.webp');
});

it('deletes the stored file when the registry entry is deleted', function (): void {
    Unsplash::fake();

    Http::fake(['images.unsplash.com/*' => Http::response('bytes', 200, ['Content-Type' => 'image/jpeg'])]);

    $asset = Unsplash::import(Photo::fromResponse(FakeUnsplash::photoPayload()));
    $path = $asset->path;

    $asset->delete();

    Storage::disk('local')->assertMissing($path);
});

it('reports a transfer failure instead of writing an empty file', function (): void {
    Unsplash::fake();

    Http::fake(['images.unsplash.com/*' => Http::response('', 503)]);

    Unsplash::import(Photo::fromResponse(FakeUnsplash::photoPayload()));
})->throws(DownloadFailedException::class);
