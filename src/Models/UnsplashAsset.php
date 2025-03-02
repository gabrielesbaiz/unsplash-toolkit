<?php

namespace Gabrielesbaiz\UnsplashToolkit\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use MarkSitko\LaravelUnsplash\Unsplash;
use Gabrielesbaiz\UnsplashToolkit\UnsplashToolkit;

class UnsplashAsset extends Model
{
    protected $fillable = [
        'unsplash_id',
        'name',
        'author',
        'author_link',
    ];

    /**
     * Creates a new instance of the unsplash api client.
     *
     * @return UnsplashToolkit
     */
    public static function api(): UnsplashToolkit
    {
        return new UnsplashToolkit();
    }

    /**
     * The booting method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function (UnsplashAsset $unsplashAsset) {
            $disk = config('unsplash.disk', 'local');

            $filePath = "{$unsplashAsset->name}";

            if (Storage::disk($disk)->exists($filePath)) {
                Storage::disk($disk)->delete($filePath);
            }

            DB::delete('delete from unsplashables where unsplash_asset_id = ?', [$unsplashAsset->id]);
        });
    }

    /**
     * Get all unsplashables by given model.
     * @param                                                      $model Illuminate\Database\Eloquent\Model
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function assets(Model $model)
    {
        return $this->morphedByMany($model, 'unsplashables');
    }

    /**
     * Returns a complete copyright link for a given data set.
     * @return string
     */
    public function getFullCopyrightLink()
    {
        $appName = config('unsplash.app_name', 'your_app_name');

        $authorLink = "{$this->author_link}?utm_source={$appName}&utm_medium=referral";

        $unsplashLink = "https://unsplash.com/?utm_source={$appName}&utm_medium=referral";

        return "Photo by <a href='{$authorLink}' target='_blank'>{$this->author}</a> on <a href='{$unsplashLink}' target='_blank'>Unsplash</a>";
    }
}
