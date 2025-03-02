<?php

namespace Gabrielesbaiz\UnsplashToolkit\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Gabrielesbaiz\UnsplashToolkit\UnsplashToolkit
 */
class UnsplashToolkit extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Gabrielesbaiz\UnsplashToolkit\UnsplashToolkit::class;
    }
}
