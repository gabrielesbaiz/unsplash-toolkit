<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Credentials
    |--------------------------------------------------------------------------
    |
    | Your application's Access Key and Secret Key must remain confidential.
    | The keys are never rendered into a page or sent to the browser: the
    | picker proxies its searches through a first-party route instead.
    |
    */

    'access_key' => env('UNSPLASH_ACCESS_KEY'),

    'secret_key' => env('UNSPLASH_SECRET_KEY'),

    'base_url' => env('UNSPLASH_BASE_URL', 'https://api.unsplash.com'),

    'api_version' => 'v1',

    /*
    |--------------------------------------------------------------------------
    | Compliance
    |--------------------------------------------------------------------------
    |
    | The Unsplash API Guidelines are enforced by this package rather than left
    | to the caller. See https://help.unsplash.com/api-guidelines
    |
    | - "app_name" is the utm_source of every attribution link. It is required:
    |   an unset or placeholder value throws instead of emitting a broken credit.
    |
    | - "allow_local_storage" gates downloading image bytes to your own disk.
    |   The guidelines require hotlinking the URLs returned under photo.urls,
    |   so this is off by default and only permissible with written permission
    |   from Unsplash, which must be referenced below.
    |
    */

    'compliance' => [

        'app_name' => env('UNSPLASH_APP_NAME'),

        'allow_local_storage' => env('UNSPLASH_ALLOW_LOCAL_STORAGE', false),

        'storage_permission_reference' => env('UNSPLASH_STORAGE_PERMISSION_REFERENCE'),

    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP
    |--------------------------------------------------------------------------
    |
    | "concurrency" caps how many requests a pooled batch fires at once. A rate
    | limited response is never retried: retrying it would only spend more of
    | your hourly budget.
    |
    */

    'http' => [

        'timeout' => 10,

        'connect_timeout' => 5,

        'concurrency' => 5,

        'retry' => [
            'times' => 3,
            'sleep' => 250,
            'backoff' => true,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Rate limiting
    |--------------------------------------------------------------------------
    |
    | Unsplash allows 50 requests per hour in demo mode and 5000 once your
    | application is approved for production. Curated pools are read from your
    | own database, so this budget is spent on curation, never on rendering.
    |
    */

    'rate_limit' => [

        'enabled' => true,

        'key' => 'unsplash-toolkit',

        'max_per_hour' => env('UNSPLASH_MAX_PER_HOUR', 50),

    ],

    /*
    |--------------------------------------------------------------------------
    | Response cache
    |--------------------------------------------------------------------------
    |
    | Only JSON metadata is cached. Image bytes are never cached or stored.
    |
    */

    'cache' => [

        'enabled' => true,

        'store' => env('UNSPLASH_CACHE_STORE'),

        'ttl' => 60 * 60,

        'prefix' => 'unsplash',

    ],

    /*
    |--------------------------------------------------------------------------
    | Curated pools
    |--------------------------------------------------------------------------
    |
    | A pool is a named set of approved photos. "selection_ttl" caches the
    | chosen photo so repeated renders do not re-query the database, and
    | "min_size" raises a PoolDepleted event before a pool runs dry.
    |
    */

    'pools' => [

        'default' => env('UNSPLASH_DEFAULT_POOL', 'default'),

        'selection_ttl' => 600,

        'min_size' => 5,

        'fallback_color' => '#0f172a',

    ],

    /*
    |--------------------------------------------------------------------------
    | Images
    |--------------------------------------------------------------------------
    |
    | Defaults applied to hotlinked URLs through Unsplash's dynamic image
    | parameters. The ixid parameter is always preserved so photo views are
    | reported back to the photographer.
    |
    */

    'images' => [

        'quality' => 80,

        'format' => 'jpg',

        'fit' => 'crop',

        'srcset_widths' => [640, 960, 1280, 1920, 2560],

        'lazy' => true,

    ],

    /*
    |--------------------------------------------------------------------------
    | Database
    |--------------------------------------------------------------------------
    */

    'database' => [

        'connection' => env('UNSPLASH_DB_CONNECTION'),

        'assets_table' => 'unsplash_assets',

        'pivot_table' => 'unsplashables',

    ],

    /*
    |--------------------------------------------------------------------------
    | Picker
    |--------------------------------------------------------------------------
    |
    | The first-party proxy route backing the admin picker. It keeps the access
    | key server side. Restrict it with your own auth middleware.
    |
    */

    'picker' => [

        'enabled' => true,

        'route_prefix' => 'unsplash-toolkit',

        'middleware' => ['web', 'auth'],

    ],

    /*
    |--------------------------------------------------------------------------
    | Storage (gated)
    |--------------------------------------------------------------------------
    |
    | Only used when compliance.allow_local_storage is enabled.
    |
    */

    'storage' => [

        'disk' => env('UNSPLASH_STORAGE_DISK', 'local'),

        'path' => 'unsplash',

    ],

    /*
    |--------------------------------------------------------------------------
    | Queue
    |--------------------------------------------------------------------------
    */

    'queue' => [

        'connection' => env('UNSPLASH_QUEUE_CONNECTION'),

        'queue' => env('UNSPLASH_QUEUE'),

    ],

    'events' => true,

];
