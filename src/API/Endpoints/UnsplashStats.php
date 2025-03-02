<?php

namespace Gabrielesbaiz\UnsplashToolkit\API\Endpoints;

trait UnsplashStats
{
    /**
     * Totals.
     * Get a list of counts for all of Unsplash.
     *
     * @see https://unsplash.com/documentation#totals
     *
     * @return $this
     */
    public function totalStats(): self
    {
        $this->apiCall = [
            'endpoint' => 'stats/total',
        ];

        return $this;
    }

    /**
     * Month.
     * Get the overall Unsplash stats for the past 30 days.
     *
     * @see https://unsplash.com/documentation#month
     *
     * @return $this
     */
    public function monthlyStats(): self
    {
        $this->apiCall = [
            'endpoint' => 'stats/month',
        ];

        return $this;
    }
}
