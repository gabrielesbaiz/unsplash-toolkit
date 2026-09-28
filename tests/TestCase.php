<?php

declare(strict_types=1);

namespace Gabrielesbaiz\UnsplashToolkit\Tests;

use Gabrielesbaiz\UnsplashToolkit\UnsplashToolkitServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Gabrielesbaiz\\UnsplashToolkit\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    /**
     * Get the package providers.
     *
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            UnsplashToolkitServiceProvider::class,
        ];
    }

    /**
     * Define the environment the tests run in.
     */
    public function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        config()->set('cache.default', 'array');

        // A real key is never used: every test drives the fake client or Http::fake().
        config()->set('unsplash-toolkit.access_key', 'test-access-key');
        config()->set('unsplash-toolkit.compliance.app_name', 'Test App');
        config()->set('unsplash-toolkit.cache.enabled', false);
        config()->set('unsplash-toolkit.rate_limit.enabled', false);

        // The picker proxy is auth protected by default; tests drive it directly.
        config()->set('unsplash-toolkit.picker.middleware', []);
    }

    /**
     * Run the package migrations.
     */
    protected function defineDatabaseMigrations(): void
    {
        foreach ([
            'create_unsplash_assets_table',
            'create_unsplashables_table',
        ] as $migration) {
            (include __DIR__."/../database/migrations/{$migration}.php.stub")->up();
        }
    }
}
