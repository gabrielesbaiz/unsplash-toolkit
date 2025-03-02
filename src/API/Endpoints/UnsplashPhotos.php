<?php

namespace Gabrielesbaiz\UnsplashToolkit\API\Endpoints;

trait UnsplashPhotos
{
    /**
     * List photos.
     * Get a single page from the list of all photos.
     *
     * @see https://unsplash.com/documentation#list-photos
     *
     * @return $this
     */
    public function photos(): self
    {
        $this->apiCall = [
            'endpoint' => 'photos',
        ];

        return $this;
    }

    /**
     * Get a photo.
     * Retrieve a single photo.
     *
     * @see https://unsplash.com/documentation#get-a-photo
     *
     * @param  string $id the photo ID
     * @return $this
     */
    public function photo(string $id): self
    {
        $this->apiCall = [
            'endpoint' => "photos/{$id}",
        ];

        return $this;
    }

    /**
     * Get a random photo.
     *
     * @see https://unsplash.com/documentation#get-a-random-photo
     *
     * @return $this
     */
    public function randomPhoto(): self
    {
        $this->apiCall = [
            'endpoint' => 'photos/random',
        ];

        return $this;
    }

    /**
     * Get a photo’s statistics.
     *
     * @see https://unsplash.com/documentation#get-a-photos-statistics
     *
     * @param  string $id the photo ID
     * @return $this
     */
    public function photosStatistics(string $id): self
    {
        $this->apiCall = [
            'endpoint' => "photos/{$id}/statistics",
        ];

        return $this;
    }

    /**
     * Track a photo download.
     *
     * @see https://unsplash.com/documentation#track-a-photo-download
     *
     * @param  string $id the photo ID
     * @return $this
     */
    public function trackPhotoDownload(string $id): self
    {
        $this->apiCall = [
            'endpoint' => "photos/{$id}/download",
        ];

        return $this;
    }
}
