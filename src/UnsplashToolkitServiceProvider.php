<?php

namespace Gabrielesbaiz\UnsplashToolkit;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class UnsplashToolkitServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('unsplash-toolkit')
            ->hasConfigFile()
            ->hasMigration('create_unsplash_assets_table')
            ->hasMigration('create_unsplashables_table');
    }
}
