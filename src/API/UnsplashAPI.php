<?php

namespace Gabrielesbaiz\UnsplashToolkit\API;

use Gabrielesbaiz\UnsplashToolkit\Queries\QueryBuilder;
use Gabrielesbaiz\UnsplashToolkit\API\Endpoints\UnsplashStats;
use Gabrielesbaiz\UnsplashToolkit\API\Endpoints\UnsplashUsers;
use Gabrielesbaiz\UnsplashToolkit\API\Endpoints\UnsplashPhotos;
use Gabrielesbaiz\UnsplashToolkit\API\Endpoints\UnsplashSearch;
use Gabrielesbaiz\UnsplashToolkit\API\Endpoints\UnsplashCollections;

trait UnsplashAPI
{
    use QueryBuilder,
        UnsplashCollections,
        UnsplashPhotos,
        UnsplashSearch,
        UnsplashStats,
        UnsplashUsers;

    /**
     * Associative array with the HTTP verb and the endpoint.
     *
     * @var array<string, mixed>
     */
    public array $apiCall = [];
}
