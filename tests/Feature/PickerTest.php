<?php

declare(strict_types=1);

use Gabrielesbaiz\UnsplashToolkit\Facades\Unsplash;
use Gabrielesbaiz\UnsplashToolkit\Models\UnsplashAsset;
use Illuminate\Support\Facades\Route;

/**
 * The picker proxy is the contract a client-side field is built against, so the
 * payload shape is pinned here.
 */
beforeEach(function (): void {
    Unsplash::fake();
});

it('returns everything a picker grid needs to lay out a tile', function (): void {
    $response = $this->getJson(route('unsplash-toolkit.search', ['query' => 'cars']));

    $response->assertOk()->assertJsonStructure([
        'total',
        'total_pages',
        'page',
        'results' => [[
            'id', 'thumb', 'width', 'height', 'aspect_ratio', 'color', 'blur_hash',
            'alt', 'author', 'author_username', 'author_url', 'unsplash_url',
            'attribution_html', 'attribution_text',
        ]],
    ]);
});

it('hands the client attribution links that are already tagged', function (): void {
    $response = $this->getJson(route('unsplash-toolkit.search', ['query' => 'cars']));

    $first = $response->json('results.0');

    expect($first['author_url'])
        ->toContain('utm_source=Test%20App')
        ->toContain('utm_medium=referral')
        ->and($first['unsplash_url'])->toContain('utm_medium=referral')
        ->and($first['attribution_html'])->toContain('rel="noopener noreferrer"');
});

it('keeps the ixid on picker thumbnails', function (): void {
    $response = $this->getJson(route('unsplash-toolkit.search', ['query' => 'cars']));

    expect($response->json('results.0.thumb'))->toContain('ixid=');
});

it('never leaks the access key to the client', function (): void {
    $response = $this->getJson(route('unsplash-toolkit.search', ['query' => 'cars']));

    expect($response->getContent())->not->toContain('test-access-key');
});

it('requires a search term', function (): void {
    $this->postJson(route('unsplash-toolkit.search'))->assertStatus(405);

    $this->getJson(route('unsplash-toolkit.search'))->assertStatus(422);
});

it('curates the chosen photos and returns their primary keys', function (): void {
    $response = $this->postJson(route('unsplash-toolkit.curate'), [
        'ids' => ['aaa1111aaaa', 'bbb2222bbbb'],
        'pool' => 'login-backgrounds',
    ]);

    $response->assertOk()
        ->assertJsonPath('curated', 2)
        ->assertJsonPath('pool', 'login-backgrounds')
        ->assertJsonStructure(['assets' => [['id', 'unsplash_id', 'thumb', 'alt', 'color']]]);

    $ids = collect($response->json('assets'))->pluck('id');

    // The primary keys are what a form field attaches to a record.
    expect(UnsplashAsset::query()->whereIn('id', $ids)->count())->toBe(2);
});

it('refuses an empty selection', function (): void {
    $this->postJson(route('unsplash-toolkit.curate'), ['ids' => []])->assertStatus(422);
});

it('caps how many photos one request may curate', function (): void {
    $this->postJson(route('unsplash-toolkit.curate'), [
        'ids' => array_fill(0, 40, 'aaa1111aaaa'),
    ])->assertStatus(422);
});

it('applies the configured middleware to the proxy routes', function (): void {
    $route = Route::getRoutes()->getByName('unsplash-toolkit.search');

    expect($route)->not->toBeNull()
        // Whatever picker.middleware holds is what guards the endpoint.
        ->and($route->gatherMiddleware())->toBe(config('unsplash-toolkit.picker.middleware'));
});
