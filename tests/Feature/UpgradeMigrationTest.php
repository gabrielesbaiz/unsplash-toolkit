<?php

declare(strict_types=1);

use Gabrielesbaiz\UnsplashToolkit\Enums\AssetStatus;
use Gabrielesbaiz\UnsplashToolkit\Models\UnsplashAsset;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The v1 -> v2 upgrade path, exercised against a real v1 schema.
 *
 * Every defect this file covers was found by running the migration against an
 * actual 1.x installation rather than by reading it.
 */
function buildV1Schema(bool $withPivotKey = false): void
{
    Schema::dropIfExists('unsplashables');
    Schema::dropIfExists('unsplash_assets');

    // Exactly what 1.x shipped: no registry columns, NOT NULL name/author.
    Schema::create('unsplash_assets', function (Blueprint $table): void {
        $table->bigIncrements('id');
        $table->string('unsplash_id', 32);
        $table->string('name')->unique();
        $table->string('author');
        $table->string('author_link');
        $table->timestamps();
    });

    Schema::create('unsplashables', function (Blueprint $table) use ($withPivotKey): void {
        if ($withPivotKey) {
            // Some installations added their own corrective primary key.
            $table->bigIncrements('pivot_id');
        }
        $table->unsignedBigInteger('unsplash_asset_id');
        $table->unsignedBigInteger('unsplashables_id');
        $table->string('unsplashables_type');
    });
}

function runUpgrade(): void
{
    (include __DIR__.'/../../database/migrations/upgrade_unsplash_tables_to_v2.php.stub')->up();
}

it('carries a v1 row across without losing it', function (): void {
    buildV1Schema();

    DB::table('unsplash_assets')->insert([
        'unsplash_id' => 'Dwu85P9SOIk',
        'name' => 'abc123.jpg',
        'author' => 'Ashim D Silva',
        'author_link' => 'https://unsplash.com/@ashim',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    runUpgrade();

    $asset = UnsplashAsset::query()->first();

    expect($asset)->not->toBeNull()
        ->and($asset->unsplash_id)->toBe('Dwu85P9SOIk')
        // v1 called the photographer "author"; v2 reads "author_name"
        ->and($asset->author_name)->toBe('Ashim D Silva')
        ->and($asset->author_link)->toBe('https://unsplash.com/@ashim')
        // the downloaded file name becomes the local path
        ->and($asset->path)->toBe('abc123.jpg')
        ->and($asset->pool)->toBe('default')
        ->and($asset->status)->toBe(AssetStatus::Active);
});

it('can still write a new row afterwards', function (): void {
    buildV1Schema();
    runUpgrade();

    // v1's NOT NULL name/author columns survive but must not block v2 inserts.
    $asset = UnsplashAsset::query()->create([
        'unsplash_id' => 'aaa1111aaaa',
        'pool' => 'hero',
        'status' => AssetStatus::Active,
        'urls' => ['regular' => 'https://images.unsplash.com/x?ixid=y'],
        'author_name' => 'Someone',
        'author_link' => 'https://unsplash.com/@someone',
    ]);

    expect($asset->exists)->toBeTrue()
        ->and(UnsplashAsset::query()->count())->toBe(1);
});

it('gives the pivot a primary key it never had', function (): void {
    buildV1Schema();
    runUpgrade();

    expect(Schema::hasColumn('unsplashables', 'unsplashable_id'))->toBeTrue()
        ->and(Schema::hasColumn('unsplashables', 'unsplashable_type'))->toBeTrue()
        ->and(collect(Schema::getIndexes('unsplashables'))->contains(fn (array $i): bool => $i['primary'] ?? false))
        ->toBeTrue();
});

it('leaves a primary key the installation added itself alone', function (): void {
    buildV1Schema(withPivotKey: true);

    runUpgrade();

    // Adding a second auto-increment would fail outright.
    expect(Schema::hasColumn('unsplashables', 'pivot_id'))->toBeTrue()
        ->and(Schema::hasColumn('unsplashables', 'id'))->toBeFalse();
});

it('preserves pivot rows when it rebuilds the table', function (): void {
    buildV1Schema();

    DB::table('unsplashables')->insert([
        'unsplash_asset_id' => 1,
        'unsplashables_id' => 42,
        'unsplashables_type' => 'App\\Models\\Article',
    ]);

    runUpgrade();

    $row = DB::table('unsplashables')->first();

    expect($row)->not->toBeNull()
        ->and($row->unsplash_asset_id)->toBe(1)
        ->and((int) $row->unsplashable_id)->toBe(42)
        ->and($row->unsplashable_type)->toBe('App\\Models\\Article');
});

it('is safe to run twice', function (): void {
    buildV1Schema();
    runUpgrade();
    runUpgrade();

    expect(Schema::hasColumn('unsplash_assets', 'pool'))->toBeTrue();
});

it('renders attribution for a row that has not been backfilled yet', function (): void {
    buildV1Schema();
    runUpgrade();

    $asset = UnsplashAsset::query()->create([
        'unsplash_id' => 'bbb2222bbbb',
        'pool' => 'default',
        'author_link' => 'https://unsplash.com/@someone',
    ]);

    // author_name is still null until unsplash:refresh --backfill runs, and
    // rendering a credit must not fatal in the meantime.
    expect($asset->author_name)->toBeNull()
        ->and($asset->attribution()->toHtml())->toContain('Unsplash');
});
