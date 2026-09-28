<?php

declare(strict_types=1);

use Gabrielesbaiz\UnsplashToolkit\Facades\Unsplash;
use Gabrielesbaiz\UnsplashToolkit\Models\UnsplashAsset;
use Gabrielesbaiz\UnsplashToolkit\Support\Config;

it('passes the audit on a correctly configured application', function (): void {
    UnsplashAsset::factory()->count(10)->create();

    $this->artisan('unsplash:doctor')->assertSuccessful();
});

it('fails the audit when no attribution name is configured', function (): void {
    config()->set('unsplash-toolkit.compliance.app_name', null);

    $this->artisan('unsplash:doctor')->assertFailed();
});

it('fails the audit when the app name is still the placeholder', function (): void {
    config()->set('unsplash-toolkit.compliance.app_name', Config::APP_NAME_PLACEHOLDER);

    $this->artisan('unsplash:doctor')->assertFailed();
});

it('fails the audit when storage is enabled with no recorded permission', function (): void {
    config()->set('unsplash-toolkit.compliance.allow_local_storage', true);
    config()->set('unsplash-toolkit.compliance.storage_permission_reference', null);

    $this->artisan('unsplash:doctor')->assertFailed();
});

it('fails the audit when no access key is set', function (): void {
    config()->set('unsplash-toolkit.access_key', null);

    $this->artisan('unsplash:doctor')->assertFailed();
});

it('warns about a pool that is running dry', function (): void {
    config()->set('unsplash-toolkit.pools.min_size', 10);

    UnsplashAsset::factory()->count(2)->pool('hero')->create();

    $this->artisan('unsplash:doctor')->assertSuccessful();
    $this->artisan('unsplash:doctor --strict')->assertFailed();
});

it('curates a photo from the command line', function (): void {
    Unsplash::fake();

    $this->artisan('unsplash:curate', ['id' => ['Dwu85P9SOIk'], '--pool' => 'hero'])
        ->assertSuccessful();

    expect(UnsplashAsset::query()->where('pool', 'hero')->count())->toBe(1);
});

it('reports a failure when a photo cannot be curated', function (): void {
    Unsplash::fake()->missing('gone');

    $this->artisan('unsplash:curate', ['id' => ['gone']])->assertFailed();
});

it('searches from the command line', function (): void {
    Unsplash::fake();

    $this->artisan('unsplash:search', ['query' => 'cars'])->assertSuccessful();
});

it('lists pool health', function (): void {
    UnsplashAsset::factory()->count(3)->pool('hero')->create();
    UnsplashAsset::factory()->count(2)->pool('hero')->unavailable()->create();

    $this->artisan('unsplash:pools')->assertSuccessful();
});

it('verifies curated photos and marks removed ones unavailable', function (): void {
    $fake = Unsplash::fake();

    $asset = UnsplashAsset::factory()->create(['last_verified_at' => null]);
    $fake->missing($asset->unsplash_id);

    $this->artisan('unsplash:verify')->assertSuccessful();

    expect($asset->fresh()?->status->value)->toBe('unavailable');
});

it('backfills rows that have no hotlink URLs', function (): void {
    Unsplash::fake();

    UnsplashAsset::factory()->create(['urls' => [], 'download_location' => null]);

    $this->artisan('unsplash:refresh', ['--backfill' => true, '--assign-pool' => 'login-backgrounds'])
        ->assertSuccessful();

    $asset = UnsplashAsset::query()->first();

    expect($asset?->pool)->toBe('login-backgrounds')
        ->and($asset?->urls)->toHaveKey('regular');
});

it('prunes photos that have been unavailable for long enough', function (): void {
    UnsplashAsset::factory()->unavailable()->create(['last_verified_at' => now()->subDays(120)]);
    UnsplashAsset::factory()->unavailable()->create(['last_verified_at' => now()]);

    $this->artisan('unsplash:prune')->expectsConfirmation('Delete 1 unavailable photos?', 'yes')->assertSuccessful();

    expect(UnsplashAsset::query()->count())->toBe(1);
});
