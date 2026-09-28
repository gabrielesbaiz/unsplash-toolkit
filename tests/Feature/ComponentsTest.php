<?php

declare(strict_types=1);

use Gabrielesbaiz\UnsplashToolkit\Models\UnsplashAsset;

it('renders a hotlinked image with a credit', function (): void {
    $asset = UnsplashAsset::factory()->create();

    $html = (string) $this->blade('<x-unsplash::image :photo="$photo" />', ['photo' => $asset]);

    expect($html)
        ->toContain('images.unsplash.com')
        ->toContain('srcset=')
        ->toContain('ixid=')
        ->toContain('loading="lazy"')
        ->toContain('utm_source=')
        ->toContain('Photo by');
});

it('renders nothing when the pool is empty', function (): void {
    $html = (string) $this->blade('<x-unsplash::image :photo="$photo" />', ['photo' => null]);

    expect(trim($html))->toBe('');
});

it('renders no attribution for a missing photo instead of failing', function (): void {
    // The v1 login blades crashed on this exact case.
    $html = (string) $this->blade('<x-unsplash::attribution :asset="$asset" />', ['asset' => null]);

    expect(trim($html))->toBe('');
});

it('paints the dominant colour while the image loads', function (): void {
    $asset = UnsplashAsset::factory()->create(['color' => '#123456']);

    expect((string) $this->blade('<x-unsplash::image :photo="$photo" />', ['photo' => $asset]))
        ->toContain('background-color: #123456');
});

it('falls back to the configured colour when a photo has none', function (): void {
    config()->set('unsplash-toolkit.pools.fallback_color', '#abcdef');

    $asset = UnsplashAsset::factory()->create(['color' => null]);

    expect((string) $this->blade('<x-unsplash::image :photo="$photo" />', ['photo' => $asset]))
        ->toContain('background-color: #abcdef');
});

it('can suppress the inline credit when it is rendered elsewhere', function (): void {
    $asset = UnsplashAsset::factory()->create();

    expect((string) $this->blade('<x-unsplash::image :photo="$photo" :attribution="false" />', ['photo' => $asset]))
        ->not->toContain('Photo by');
});
