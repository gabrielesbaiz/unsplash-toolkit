<?php

namespace Gabrielesbaiz\UnsplashToolkit\Traits;

use Gabrielesbaiz\UnsplashToolkit\Models\UnsplashAsset;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasUnsplashables
{
    /**
     * The booting method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($model) {
            $model->unsplash()->detach();
        });
    }

    /**
     * Model may have multiple unsplash assets.
     *
     * @return MorphToMany
     */
    public function unsplash(): MorphToMany
    {
        return $this->morphToMany(UnsplashAsset::class, 'unsplashables');
    }
}
