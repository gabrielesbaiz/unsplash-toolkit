<?php

declare(strict_types=1);

use Gabrielesbaiz\UnsplashToolkit\Enums\Color;
use Gabrielesbaiz\UnsplashToolkit\Enums\Orientation;
use Gabrielesbaiz\UnsplashToolkit\Facades\Unsplash;

it('returns a new instance for every modifier', function (): void {
    $base = Unsplash::search('cars');

    $withColor = $base->color(Color::Blue);

    expect($withColor)->not->toBe($base)
        ->and($base->query())->not->toHaveKey('color');
});

it('does not leak parameters between two requests', function (): void {
    Unsplash::fake();

    $search = Unsplash::search('cats')->orientation(Orientation::Portrait);
    $photos = Unsplash::photos();

    expect($search->query())->toMatchArray(['query' => 'cats', 'orientation' => 'portrait'])
        // The v1 builder shared state on a cached facade instance, so this leaked.
        ->and($photos->query())->toBe([]);
});

it('lets one base request branch into independent variants', function (): void {
    $base = Unsplash::search('cars');

    $landscape = $base->orientation(Orientation::Landscape);
    $portrait = $base->orientation(Orientation::Portrait);

    expect($landscape->query()['orientation'])->toBe('landscape')
        ->and($portrait->query()['orientation'])->toBe('portrait');
});

it('accepts enums and plain strings alike', function (): void {
    expect(Unsplash::search('x')->color(Color::BlackAndWhite)->query()['color'])
        ->toBe('black_and_white')
        ->and(Unsplash::search('x')->color('blue')->query()['color'])->toBe('blue');
});

it('maps the term to the query parameter Unsplash expects', function (): void {
    expect(Unsplash::search('buildings')->query())->toHaveKey('query', 'buildings');
});

it('returns the decoded payload for endpoints with no photo shape', function (): void {
    Unsplash::fake()->stub('users/ashim', ['username' => 'ashim', 'total_photos' => 12]);

    expect(Unsplash::user('ashim')->get())->toBe(['username' => 'ashim', 'total_photos' => 12]);
});
