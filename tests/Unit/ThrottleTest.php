<?php

declare(strict_types=1);

use Gabrielesbaiz\UnsplashToolkit\Exceptions\RateLimitExceededException;
use Gabrielesbaiz\UnsplashToolkit\Support\Throttle;
use Illuminate\Support\Facades\Cache;

/**
 * RateLimiter::attempts() returns whatever the cache store holds. The array
 * store used by the suite holds an int, but Redis holds a string, so a return
 * type of int only held for applications on the array driver.
 */
beforeEach(function (): void {
    config()->set('unsplash-toolkit.rate_limit.enabled', true);
    config()->set('unsplash-toolkit.rate_limit.max_per_hour', 50);

    app(Throttle::class)->clear();
});

it('counts requests a store returned as a string', function (): void {
    Cache::put(config('unsplash-toolkit.rate_limit.key', 'unsplash-toolkit'), '7', 3600);

    $throttle = app(Throttle::class);

    expect($throttle->used())->toBe(7)
        ->and($throttle->remaining())->toBe(43);
});

it('counts an empty window as zero', function (): void {
    $throttle = app(Throttle::class);

    expect($throttle->used())->toBe(0)
        ->and($throttle->remaining())->toBe(50);
});

it('counts the requests it reserved', function (): void {
    $throttle = app(Throttle::class);

    $throttle->hit(3);

    expect($throttle->used())->toBe(3)
        ->and($throttle->remaining())->toBe(47)
        ->and($throttle->availableIn())->toBeInt();
});

it('raises the rate limit exception when the budget is spent', function (): void {
    config()->set('unsplash-toolkit.rate_limit.max_per_hour', 2);

    $throttle = app(Throttle::class);
    $throttle->hit(2);

    // retryAfter is typed int, so a store returning a string must not reach it raw.
    try {
        $throttle->hit();

        $this->fail('The rate limit exception was not raised.');
    } catch (RateLimitExceededException $exception) {
        expect($exception->retryAfter)->toBeInt();
    }
});
