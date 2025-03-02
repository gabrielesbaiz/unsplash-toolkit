<?php

namespace Gabrielesbaiz\UnsplashToolkit\API\Endpoints;

trait UnsplashUsers
{
    /**
     * Get a user’s public profile.
     * Retrieve public details on a given user.
     *
     * @see https://unsplash.com/documentation#get-a-users-public-profile
     *
     * @param  string $username The user’s username. Required.
     * @return $this
     */
    public function user(string $username): self
    {
        $this->apiCall = [
            'endpoint' => "users/{$username}",
        ];

        return $this;
    }

    /**
     * Get a user’s portfolio link.
     * Retrieve a single user’s portfolio link.
     *
     * @see https://unsplash.com/documentation#get-a-users-portfolio-link
     *
     * @param  string $username The user’s username. Required.
     * @return $this
     */
    public function userPortfolio(string $username): self
    {
        $this->apiCall = [
            'endpoint' => "users/{$username}/portfolio",
        ];

        return $this;
    }

    /**
     * List a user’s photos.
     * Get a list of photos uploaded by a user.
     *
     * @see https://unsplash.com/documentation#list-a-users-photos
     *
     * @param  string $username The user’s username. Required.
     * @return $this
     */
    public function userPhotos(string $username): self
    {
        $this->apiCall = [
            'endpoint' => "users/{$username}/photos",
        ];

        return $this;
    }

    /**
     * List a user’s liked photos.
     * Get a list of photos liked by a user.
     *
     * @see https://unsplash.com/documentation#list-a-users-liked-photos
     *
     * @param  string $username The user’s username. Required.
     * @return $this
     */
    public function userLikes(string $username): self
    {
        $this->apiCall = [
            'endpoint' => "users/{$username}/likes",
        ];

        return $this;
    }

    /**
     * List a user’s collections.
     * Get a list of collections created by the user.
     *
     * @see https://unsplash.com/documentation#list-a-users-collections
     *
     * @param  string $username The user’s username. Required.
     * @return $this
     */
    public function userCollections(string $username): self
    {
        $this->apiCall = [
            'endpoint' => "users/{$username}/collections",
        ];

        return $this;
    }

    /**
     * Get a user’s statistics.
     * Retrieve the consolidated number of downloads, views, and likes of all user’s photos,
     * as well as the historical breakdown and average of these stats in a specific timeframe (default is 30 days).
     *
     * @see https://unsplash.com/documentation#get-a-users-statistics
     *
     * @param  string $username The user’s username. Required.
     * @return $this
     */
    public function userStatistics(string $username): self
    {
        $this->apiCall = [
            'endpoint' => "users/{$username}/statistics",
        ];

        return $this;
    }
}
