<?php

declare(strict_types=1);

use Gabrielesbaiz\UnsplashToolkit\Concerns\HasUnsplashables;
use Gabrielesbaiz\UnsplashToolkit\Models\UnsplashAsset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    Schema::create('posts', function (Blueprint $table): void {
        $table->id();
        $table->string('title')->nullable();
        $table->boolean('booted_itself')->default(false);
        $table->timestamps();
    });
});

it('attaches curated photos to a model', function (): void {
    $post = TestPost::query()->create(['title' => 'Hello']);
    $asset = UnsplashAsset::factory()->create();

    $post->unsplash()->attach($asset);

    expect($post->unsplash()->count())->toBe(1)
        ->and($post->unsplashPhoto()?->getKey())->toBe($asset->getKey());
});

it('does not replace the host model own boot method', function (): void {
    // A trait declaring boot() would silently override this.
    $post = TestPost::query()->create(['title' => 'Hello']);

    expect($post->booted_itself)->toBeTrue();
});

it('detaches photos when the host model is deleted', function (): void {
    $post = TestPost::query()->create(['title' => 'Hello']);
    $asset = UnsplashAsset::factory()->create();

    $post->unsplash()->attach($asset);
    $post->delete();

    expect(UnsplashAsset::query()->find($asset->getKey())?->attachedTo(TestPost::class)->count())->toBe(0);
});

it('clears attachments when the photo is deleted', function (): void {
    $post = TestPost::query()->create(['title' => 'Hello']);
    $asset = UnsplashAsset::factory()->create();

    $post->unsplash()->attach($asset);
    $asset->delete();

    expect($post->unsplash()->count())->toBe(0);
});

it('eager loads attached photos without an extra query per model', function (): void {
    $asset = UnsplashAsset::factory()->create();

    foreach (range(1, 5) as $i) {
        TestPost::query()->create(['title' => "Post {$i}"])->unsplash()->attach($asset);
    }

    DB::enableQueryLog();

    TestPost::query()->with('unsplash')->get();

    expect(DB::getQueryLog())->toHaveCount(2);
});

it('exposes the Unsplash page for a photo', function (): void {
    $asset = UnsplashAsset::factory()->create(['html_link' => null, 'unsplash_id' => 'abc123']);

    expect($asset->unsplashUrl())->toBe('https://unsplash.com/photos/abc123');
});

class TestPost extends Model
{
    use HasUnsplashables;

    protected $table = 'posts';

    protected $guarded = [];

    protected $casts = ['booted_itself' => 'boolean'];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $post): void {
            $post->booted_itself = true;
        });
    }
}
