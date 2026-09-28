<?php

declare(strict_types=1);

namespace Gabrielesbaiz\UnsplashToolkit\Concerns;

use Gabrielesbaiz\UnsplashToolkit\Models\UnsplashAsset;
use Gabrielesbaiz\UnsplashToolkit\Support\Config;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * Attaches curated Unsplash photos to a model.
 *
 * The boot hook is named bootHasUnsplashables so Laravel invokes it alongside
 * the model's own boot() instead of replacing it, which is what a plain boot()
 * in a trait does.
 *
 * @phpstan-require-extends Model
 */
trait HasUnsplashables
{
    /**
     * Bootstrap the trait.
     */
    public static function bootHasUnsplashables(): void
    {
        static::deleting(function (self $model): void {
            $model->unsplash()->detach();
        });
    }

    /**
     * Get the curated photos attached to this model.
     *
     * @return MorphToMany<UnsplashAsset, $this>
     */
    public function unsplash(): MorphToMany
    {
        return $this->morphToMany(
            UnsplashAsset::class,
            'unsplashable',
            app(Config::class)->pivotTable(),
        )->withTimestamps();
    }

    /**
     * Get the first curated photo attached to this model.
     */
    public function unsplashPhoto(): ?UnsplashAsset
    {
        /** @var UnsplashAsset|null $asset */
        $asset = $this->unsplash()->first();

        return $asset;
    }
}
