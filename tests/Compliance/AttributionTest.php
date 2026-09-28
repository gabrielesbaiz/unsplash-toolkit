<?php

declare(strict_types=1);

use Gabrielesbaiz\UnsplashToolkit\Data\Attribution;
use Gabrielesbaiz\UnsplashToolkit\Data\Photo;
use Gabrielesbaiz\UnsplashToolkit\Drivers\FakeUnsplash;
use Gabrielesbaiz\UnsplashToolkit\Exceptions\MissingAttributionNameException;
use Gabrielesbaiz\UnsplashToolkit\Support\Config;

/**
 * C5: "your application must attribute Unsplash, the Unsplash photographer, and
 * contain a link back to their Unsplash profile", tagged with utm parameters.
 */
it('credits the photographer and Unsplash with both utm parameters', function (): void {
    $photo = Photo::fromResponse(FakeUnsplash::photoPayload());

    $html = $photo->attribution()->toHtml();

    expect($html)
        ->toContain('utm_source=Test%20App')
        ->toContain('utm_medium=referral')
        ->toContain('unsplash.com')
        ->toContain('Photo by');
});

it('escapes an author name supplied by the API', function (): void {
    $attribution = new Attribution(
        authorName: '<script>alert(1)</script>',
        authorLink: 'https://unsplash.com/@evil',
        appName: 'Test App',
    );

    expect($attribution->toHtml())
        ->not->toContain('<script>')
        ->toContain('&lt;script&gt;');
});

it('escapes a malicious author link', function (): void {
    $attribution = new Attribution(
        authorName: 'Someone',
        authorLink: 'https://unsplash.com/@x?a="onload="alert(1)',
        appName: 'Test App',
    );

    expect($attribution->toHtml())->not->toContain('onload="alert(1)"');
});

it('merges the utm parameters into a link that already has a query string', function (): void {
    $attribution = new Attribution(
        authorName: 'Someone',
        authorLink: 'https://unsplash.com/@someone?ref=existing',
        appName: 'Test App',
    );

    $url = $attribution->authorUrl();

    expect($url)
        ->toContain('ref=existing')
        ->toContain('utm_source=Test%20App')
        ->and(substr_count($url, '?'))->toBe(1);
});

it('adds rel="noopener noreferrer" to every outbound link', function (): void {
    $photo = Photo::fromResponse(FakeUnsplash::photoPayload());

    expect(substr_count($photo->attribution()->toHtml(), 'rel="noopener noreferrer"'))->toBe(2);
});

it('throws rather than emitting a credit with no utm_source', function (): void {
    config()->set('unsplash-toolkit.compliance.app_name', null);

    Photo::fromResponse(FakeUnsplash::photoPayload())->attribution();
})->throws(MissingAttributionNameException::class);

it('treats the legacy placeholder app name as missing', function (): void {
    config()->set('unsplash-toolkit.compliance.app_name', Config::APP_NAME_PLACEHOLDER);

    Photo::fromResponse(FakeUnsplash::photoPayload())->attribution();
})->throws(MissingAttributionNameException::class);
