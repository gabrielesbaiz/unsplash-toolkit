<?php

declare(strict_types=1);

namespace Gabrielesbaiz\UnsplashToolkit;

use Gabrielesbaiz\UnsplashToolkit\Commands\CacheClearCommand;
use Gabrielesbaiz\UnsplashToolkit\Commands\CurateCommand;
use Gabrielesbaiz\UnsplashToolkit\Commands\DoctorCommand;
use Gabrielesbaiz\UnsplashToolkit\Commands\PoolsCommand;
use Gabrielesbaiz\UnsplashToolkit\Commands\PruneCommand;
use Gabrielesbaiz\UnsplashToolkit\Commands\RefreshCommand;
use Gabrielesbaiz\UnsplashToolkit\Commands\SearchCommand;
use Gabrielesbaiz\UnsplashToolkit\Commands\StatusCommand;
use Gabrielesbaiz\UnsplashToolkit\Commands\VerifyCommand;
use Gabrielesbaiz\UnsplashToolkit\Contracts\UnsplashClient;
use Gabrielesbaiz\UnsplashToolkit\Drivers\UnsplashApi;
use Gabrielesbaiz\UnsplashToolkit\Http\Controllers\PickerController;
use Gabrielesbaiz\UnsplashToolkit\Support\Compliance;
use Gabrielesbaiz\UnsplashToolkit\Support\Config;
use Gabrielesbaiz\UnsplashToolkit\Support\Curator;
use Gabrielesbaiz\UnsplashToolkit\Support\Downloader;
use Gabrielesbaiz\UnsplashToolkit\Support\ImageUrl;
use Gabrielesbaiz\UnsplashToolkit\Support\ResponseCache;
use Gabrielesbaiz\UnsplashToolkit\Support\Throttle;
use Gabrielesbaiz\UnsplashToolkit\View\Components\Attribution as AttributionComponent;
use Gabrielesbaiz\UnsplashToolkit\View\Components\Image as ImageComponent;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class UnsplashToolkitServiceProvider extends PackageServiceProvider
{
    /**
     * Configure the package.
     */
    public function configurePackage(Package $package): void
    {
        $package
            ->name('unsplash-toolkit')
            ->hasConfigFile()
            ->hasViews('unsplash')
            ->hasMigration('create_unsplash_assets_table')
            ->hasMigration('create_unsplashables_table')
            ->hasMigration('upgrade_unsplash_tables_to_v2')
            ->hasCommands([
                SearchCommand::class,
                CurateCommand::class,
                PoolsCommand::class,
                VerifyCommand::class,
                RefreshCommand::class,
                StatusCommand::class,
                DoctorCommand::class,
                CacheClearCommand::class,
                PruneCommand::class,
            ]);
    }

    /**
     * Register the package services.
     */
    public function packageRegistered(): void
    {
        $this->app->singleton(Config::class, fn ($app) => new Config($app['config']));
        $this->app->singleton(Compliance::class);
        $this->app->singleton(ImageUrl::class);
        $this->app->singleton(ResponseCache::class);
        $this->app->singleton(Throttle::class);
        $this->app->singleton(Downloader::class);
        $this->app->singleton(Curator::class);

        $this->app->singleton(UnsplashClient::class, UnsplashApi::class);
        $this->app->singleton(UnsplashToolkit::class);

        $this->app->alias(UnsplashToolkit::class, 'unsplash-toolkit');
        $this->app->alias(UnsplashToolkit::class, 'unsplash');
    }

    /**
     * Bootstrap the package services.
     */
    public function packageBooted(): void
    {
        $this->registerComponents();
        $this->registerPickerRoutes();
    }

    /**
     * Register the package Blade components.
     */
    protected function registerComponents(): void
    {
        Blade::component('unsplash::image', ImageComponent::class);
        Blade::component('unsplash::attribution', AttributionComponent::class);
    }

    /**
     * Register the first-party proxy route backing the admin picker.
     *
     * The search runs server side so the access key is never sent to a browser.
     */
    protected function registerPickerRoutes(): void
    {
        $config = $this->app->make(Config::class);

        if (! $config->pickerEnabled() || $this->app->routesAreCached()) {
            return;
        }

        Route::group([
            'prefix' => $config->pickerPrefix(),
            'middleware' => $config->pickerMiddleware(),
            'as' => 'unsplash-toolkit.',
        ], function (): void {
            Route::get('search', [PickerController::class, 'search'])->name('search');
            Route::post('curate', [PickerController::class, 'curate'])->name('curate');
        });
    }
}
