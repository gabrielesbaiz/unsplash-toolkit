<?php

declare(strict_types=1);

use Gabrielesbaiz\UnsplashToolkit\Data\Photo;
use Gabrielesbaiz\UnsplashToolkit\Drivers\FakeUnsplash;
use Gabrielesbaiz\UnsplashToolkit\Exceptions\UnsplashException;
use Gabrielesbaiz\UnsplashToolkit\Facades\Unsplash;
use Illuminate\Support\Facades\Http;

it('caches a repeated search instead of spending the API budget twice', function (): void {
    config()->set('unsplash-toolkit.cache.enabled', true);

    Http::fake(['api.unsplash.com/*' => Http::response(['total' => 1, 'total_pages' => 1, 'results' => []], 200)]);

    Unsplash::search('cars')->perPage(10)->get();
    Unsplash::search('cars')->perPage(10)->get();

    Http::assertSentCount(1);
});

it('treats a different search as a different request', function (): void {
    config()->set('unsplash-toolkit.cache.enabled', true);

    Http::fake(['api.unsplash.com/*' => Http::response(['total' => 1, 'total_pages' => 1, 'results' => []], 200)]);

    Unsplash::search('cars')->get();
    Unsplash::search('boats')->get();

    Http::assertSentCount(2);
});

it('never caches a download event', function (): void {
    config()->set('unsplash-toolkit.cache.enabled', true);

    Http::fake(['api.unsplash.com/*' => Http::response([], 200)]);

    $photo = Photo::fromResponse(
        FakeUnsplash::photoPayload()
    );

    Unsplash::trackDownload($photo);
    Unsplash::trackDownload($photo);

    // Two selections must report two events, or the photographer is undercredited.
    Http::assertSentCount(2);
});

it('fetches several photos in one pooled batch rather than serially', function (): void {
    config()->set('unsplash-toolkit.cache.enabled', false);

    Http::fake(function ($request) {
        $id = basename(parse_url((string) $request->url(), PHP_URL_PATH) ?: '');

        return Http::response(FakeUnsplash::photoPayload($id), 200);
    });

    $photos = Unsplash::photosByIds(['aaa', 'bbb', 'ccc']);

    expect($photos)->toHaveCount(3);

    Http::assertSentCount(3);
});

it('retries a server error with backoff', function (): void {
    config()->set('unsplash-toolkit.http.retry.times', 3);
    config()->set('unsplash-toolkit.http.retry.sleep', 1);

    Http::fakeSequence()
        ->push([], 500)
        ->push([], 500)
        ->push(['photos' => 1], 200);

    Unsplash::stats();

    Http::assertSentCount(3);
});

it('does not retry a client error', function (): void {
    config()->set('unsplash-toolkit.http.retry.times', 3);

    Http::fake(['api.unsplash.com/*' => Http::response([], 400)]);

    try {
        Unsplash::stats();
    } catch (UnsplashException) {
        // A malformed request will not succeed on a second attempt.
    }

    Http::assertSentCount(1);
});
