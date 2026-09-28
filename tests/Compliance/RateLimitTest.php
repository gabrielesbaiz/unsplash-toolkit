<?php

declare(strict_types=1);

use Gabrielesbaiz\UnsplashToolkit\Exceptions\RateLimitExceededException;
use Gabrielesbaiz\UnsplashToolkit\Facades\Unsplash;
use Gabrielesbaiz\UnsplashToolkit\Support\Throttle;
use Illuminate\Support\Facades\Http;

/**
 * C6: "Too many requests too quickly will get your access turned off."
 */
beforeEach(function (): void {
    config()->set('unsplash-toolkit.rate_limit.enabled', true);
    config()->set('unsplash-toolkit.rate_limit.max_per_hour', 3);

    app(Throttle::class)->clear();
});

it('refuses to exceed the configured hourly budget', function (): void {
    Http::fake(['api.unsplash.com/*' => Http::response(['photos' => 1], 200)]);

    for ($i = 0; $i < 3; $i++) {
        Unsplash::stats();
    }

    Unsplash::stats();
})->throws(RateLimitExceededException::class);

it('raises an exception when Unsplash reports the budget is spent', function (): void {
    Http::fake([
        'api.unsplash.com/*' => Http::response([], 403, [
            'X-Ratelimit-Limit' => '50',
            'X-Ratelimit-Remaining' => '0',
            'Retry-After' => '900',
        ]),
    ]);

    try {
        Unsplash::stats();

        $this->fail('The rate limit exception was not raised.');
    } catch (RateLimitExceededException $exception) {
        expect($exception->retryAfter)->toBe(900);
    }
});

it('does not retry a rate limited response', function (): void {
    config()->set('unsplash-toolkit.http.retry.times', 3);

    Http::fake([
        'api.unsplash.com/*' => Http::response([], 403, [
            'X-Ratelimit-Limit' => '50',
            'X-Ratelimit-Remaining' => '0',
        ]),
    ]);

    try {
        Unsplash::stats();
    } catch (RateLimitExceededException) {
        // Retrying an exhausted budget only spends more of it.
    }

    Http::assertSentCount(1);
});

it('reads the budget Unsplash reports on a successful response', function (): void {
    Http::fake([
        'api.unsplash.com/*' => Http::response(['photos' => 1], 200, [
            'X-Ratelimit-Limit' => '50',
            'X-Ratelimit-Remaining' => '37',
        ]),
    ]);

    Unsplash::stats();

    $rateLimit = Unsplash::rateLimit();

    expect($rateLimit?->limit)->toBe(50)
        ->and($rateLimit?->remaining)->toBe(37)
        ->and($rateLimit?->used())->toBe(13)
        ->and($rateLimit?->isDemo())->toBeTrue();
});
