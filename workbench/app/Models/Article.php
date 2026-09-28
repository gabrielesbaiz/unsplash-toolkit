<?php

declare(strict_types=1);

namespace Workbench\App\Models;

use Gabrielesbaiz\UnsplashToolkit\Concerns\HasUnsplashables;
use Illuminate\Database\Eloquent\Model;

/**
 * A sample model showing how a record carries curated Unsplash photos.
 */
class Article extends Model
{
    use HasUnsplashables;

    protected $guarded = [];
}
