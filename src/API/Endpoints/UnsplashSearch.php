<?php

namespace Gabrielesbaiz\UnsplashToolkit\API\Endpoints;

trait UnsplashSearch
{
    /**
     * Search photos.
     * Get a single page of photo results for a query.
     *
     * @see https://unsplash.com/documentation#search-photos
     *
     * @return $this
     */
    public function search(): self
    {
        $this->apiCall = [
            'endpoint' => 'search/photos',
        ];

        return $this;
    }

    /**
     * Search collections.
     * Get a single page of collection results for a query.
     *
     * @see https://unsplash.com/documentation#search-collections
     *
     * @return $this
     */
    public function searchCollections(): self
    {
        $this->apiCall = [
            'endpoint' => 'search/collections',
        ];

        return $this;
    }

    /**
     * Search users.
     * Get a single page of user results for a query.
     *
     * @see https://unsplash.com/documentation#search-users
     *
     * @return $this
     */
    public function searchUsers(): self
    {
        $this->apiCall = [
            'endpoint' => 'search/users',
        ];

        return $this;
    }
}
