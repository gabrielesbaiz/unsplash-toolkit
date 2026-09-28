<?php

declare(strict_types=1);

use Gabrielesbaiz\UnsplashToolkit\Data\CollectionResource;
use Gabrielesbaiz\UnsplashToolkit\Data\Photo;
use Gabrielesbaiz\UnsplashToolkit\Data\PhotoCollection;
use Gabrielesbaiz\UnsplashToolkit\Data\SearchResult;
use Gabrielesbaiz\UnsplashToolkit\Enums\Color;
use Gabrielesbaiz\UnsplashToolkit\Enums\OrderBy;
use Gabrielesbaiz\UnsplashToolkit\Enums\Orientation;
use Gabrielesbaiz\UnsplashToolkit\Enums\Size;
use Gabrielesbaiz\UnsplashToolkit\Exceptions\PhotoNotFoundException;
use Gabrielesbaiz\UnsplashToolkit\Facades\Unsplash;
use Gabrielesbaiz\UnsplashToolkit\Models\UnsplashAsset;
use Illuminate\Support\Collection;

/**
 * The code samples published in the documentation site, executed.
 *
 * The README is a front door and carries no PHP; the samples live in docs/index.html
 * and on https://gabrielesbaiz.github.io/unsplash-toolkit/. If a signature changes,
 * this fails before the documentation goes stale.
 */
beforeEach(function (): void {
    Unsplash::fake();
});

it('runs the quick start', function (): void {
    $photo = Unsplash::search('mountain road')->first();

    expect($photo)->toBeInstanceOf(Photo::class);

    Unsplash::curate($photo, pool: 'login-backgrounds');

    expect(UnsplashAsset::cachedRandom('login-backgrounds'))->toBeInstanceOf(UnsplashAsset::class);
});

it('runs the search sample', function (): void {
    $results = Unsplash::search('buildings')
        ->color(Color::BlackAndWhite)
        ->orientation(Orientation::Squarish)
        ->orderBy(OrderBy::Relevant)
        ->perPage(30)
        ->page(1)
        ->get();

    expect($results)->toBeInstanceOf(SearchResult::class)
        ->and($results->photos)->toBeInstanceOf(PhotoCollection::class)
        ->and($results->photos->ids())->toBeArray()
        ->and($results->hasMorePages())->toBeBool();

    foreach ($results as $photo) {
        expect($photo->id)->toBeString();
    }
});

it('runs the photo samples', function (): void {
    expect(Unsplash::photo('Dwu85P9SOIk'))->toBeInstanceOf(Photo::class)
        ->and(Unsplash::photos()->perPage(20)->orderBy(OrderBy::Latest)->get())->toBeInstanceOf(PhotoCollection::class)
        ->and(Unsplash::photosByIds(['Dwu85P9SOIk', 'aaa1111aaaa']))->toHaveCount(2);
});

it('runs the random photo samples', function (): void {
    expect(Unsplash::random()->term('car')->orientation(Orientation::Landscape)->first())
        ->toBeInstanceOf(Photo::class)
        ->and(Unsplash::random()->term('car')->count(5)->get())->toHaveCount(5)
        ->and(Unsplash::random()->collections(['1234567', '8901234'])->first())
        ->toBeInstanceOf(Photo::class);
});

it('runs the collection samples', function (): void {
    $collection = Unsplash::collection('1234567');

    expect(Unsplash::collections()->perPage(10)->get())->toBeInstanceOf(Collection::class)
        ->and($collection)->toBeInstanceOf(CollectionResource::class)
        ->and(Unsplash::collectionPhotos('1234567')->perPage(30)->get())->toBeInstanceOf(PhotoCollection::class)
        ->and(Unsplash::searchCollections('architecture')->get())->toBeInstanceOf(Collection::class);
});

it('runs the user and statistics samples', function (): void {
    expect(Unsplash::userPhotos('ashim')->perPage(20)->get())->toBeInstanceOf(PhotoCollection::class)
        ->and(Unsplash::userLikes('ashim')->get())->toBeInstanceOf(PhotoCollection::class)
        ->and(Unsplash::userCollections('ashim')->get())->toBeInstanceOf(Collection::class)
        ->and(Unsplash::stats()->photos)->toBe(100)
        ->and(Unsplash::user('ashim')->get())->toBeArray()
        ->and(Unsplash::photoStatistics('Dwu85P9SOIk')->get())->toBeArray()
        ->and(Unsplash::searchUsers('ashim')->get())->toBeArray()
        ->and(Unsplash::search('cars')->toArray())->toBeArray();
});

it('runs the immutable builder sample', function (): void {
    $base = Unsplash::search('cars')->perPage(30);

    $base->orientation(Orientation::Landscape)->get();
    $base->orientation(Orientation::Portrait)->get();

    expect($base->query())->not->toHaveKey('orientation')
        ->and($base->endpoint())->toBe('search/photos')
        ->and(Unsplash::search('cars')->withQuery('lang', 'it')->query())->toHaveKey('lang', 'it');
});

it('runs the URL and srcset samples', function (): void {
    $photo = Unsplash::photo('Dwu85P9SOIk');

    expect($photo->url(Size::Regular))->toBeString()
        ->and($photo->rawUrl(Size::Thumb))->toBeString()
        ->and($photo->url(width: 1920))->toContain('w=1920')
        ->and($photo->url(width: 1920, height: 1080, quality: 70))->toContain('q=70')
        ->and($photo->url(Size::Raw, width: 2560, format: 'webp', fit: 'crop', dpr: 2))->toContain('dpr=2')
        ->and($photo->srcset())->toContain('640w')
        ->and($photo->srcset([640, 1280], quality: 60))->toContain('1280w')
        ->and($photo->srcset([640, 1280], preserveAspectRatio: false))->toContain('640w')
        ->and($photo->aspectRatio())->toBeFloat();
});

it('runs the attribution samples', function (): void {
    $credit = Unsplash::photo('Dwu85P9SOIk')->attribution();

    expect($credit->authorName)->toBeString()
        ->and($credit->authorUrl())->toContain('utm_source=')
        ->and($credit->unsplashUrl())->toContain('utm_medium=referral')
        ->and($credit->toText())->toStartWith('Photo by')
        ->and($credit->toHtml())->toContain('<a href')
        ->and($credit->toHtmlString()->toHtml())->toContain('<a href');
});

it('runs the DTO property samples', function (): void {
    $photo = Unsplash::photo('Dwu85P9SOIk');

    expect($photo->id)->toBeString()
        ->and($photo->width)->toBeInt()
        ->and($photo->height)->toBeInt()
        ->and($photo->color)->toBeString()
        ->and($photo->blurHash)->toBeString()
        ->and($photo->alt())->toBeString()
        ->and($photo->tags)->toBeArray()
        ->and($photo->likes)->toBeInt()
        ->and($photo->createdAt)->toBeString()
        ->and($photo->htmlLink)->toBeString()
        ->and($photo->downloadLocation)->toBeString()
        ->and($photo->author->name)->toBeString()
        ->and($photo->author->username)->toBeString()
        ->and($photo->author->link)->toBeString()
        ->and($photo->raw)->toBeArray()
        ->and($photo->toArray())->toBeArray()
        ->and($photo->toJson())->toBeString()
        ->and($photo['color'])->toBeString()
        ->and((string) $photo)->toBeString();
});

it('runs the curating samples', function (): void {
    $photo = Unsplash::photo('Dwu85P9SOIk');

    expect(Unsplash::curate($photo, pool: 'login-backgrounds'))->toBeInstanceOf(UnsplashAsset::class)
        ->and(Unsplash::curateMany(Unsplash::photosByIds(['aaa1111aaaa']), pool: 'hero'))->toHaveCount(1)
        ->and(Unsplash::curateCollection('1234567', pool: 'hero', perPage: 30))->toHaveCount(2);
});

it('runs the selection samples', function (): void {
    UnsplashAsset::factory()->count(6)->pool('login-backgrounds')->create();

    expect(UnsplashAsset::random('login-backgrounds'))->toBeInstanceOf(UnsplashAsset::class)
        ->and(UnsplashAsset::cachedRandom('login-backgrounds'))->toBeInstanceOf(UnsplashAsset::class)
        ->and(UnsplashAsset::cachedRandom('login-backgrounds', ttl: 60))->toBeInstanceOf(UnsplashAsset::class)
        ->and(UnsplashAsset::randomOrFail('login-backgrounds'))->toBeInstanceOf(UnsplashAsset::class)
        ->and(Unsplash::fromPool('login-backgrounds'))->toBeInstanceOf(UnsplashAsset::class)
        ->and(UnsplashAsset::pool('hero')->active()->get())->toBeEmpty();
});

it('runs the asset accessor samples', function (): void {
    $asset = UnsplashAsset::factory()->create();

    expect($asset->url(width: 1920, quality: 80))->toContain('w=1920')
        ->and($asset->srcset([640, 1280]))->toContain('640w')
        ->and($asset->placeholderColor())->toBeString()
        ->and($asset->isSelectable())->toBeTrue()
        ->and($asset->unsplashUrl())->toContain('unsplash.com')
        ->and($asset->alt())->toBeString()
        ->and($asset->attribution()->toHtml())->toContain('utm_source=');
});

it('runs the rate limit samples', function (): void {
    expect(Unsplash::throttle()->used())->toBeInt()
        ->and(Unsplash::throttle()->remaining())->toBeInt()
        ->and(Unsplash::throttle()->availableIn())->toBeInt()
        ->and(Unsplash::rateLimit()?->isDemo())->toBeTrue();
});

it('runs the fake samples', function (): void {
    $fake = Unsplash::fake();

    Unsplash::curate(Unsplash::photo('Dwu85P9SOIk'), 'hero');

    expect($fake->sentRequestTo('/download'))->toBeTrue()
        ->and($fake->requestCount())->toBe(2)
        ->and($fake->requests[1]['endpoint'])->toContain('/download');

    $fake->missing('gone-forever');

    Unsplash::photo('gone-forever');
})->throws(PhotoNotFoundException::class);
