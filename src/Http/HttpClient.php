<?php

namespace Gabrielesbaiz\UnsplashToolkit\Http;

use Exception;
use GuzzleHttp\Client;

class HttpClient
{
    const API_URL = 'https://api.unsplash.com/';

    protected Client $client;

    private string $accessKey;

    /**
     * Creates a new instance of HttpClient.
     *
     * @return $this
     */
    public function __construct()
    {
        $this->initalizeConfiguration()
            ->createClient();

        return $this;
    }

    /**
     * Initialize and store configuration values in class properties.
     *
     * @return $this
     */
    private function initalizeConfiguration(): self
    {
        $this->accessKey = config('unsplash.access_key');

        if (! $this->accessKey) {
            throw new Exception('A Unsplash-API access key must be provided');
        }

        return $this;
    }

    /**
     * Instantiate the GuzzleHttp Client.
     *
     * @return $this
     */
    private function createClient(): self
    {
        $this->client = new Client([
            'base_uri' => self::API_URL,
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Authorization' => "Client-ID {$this->accessKey}",
            ],
        ]);

        return $this;
    }
}
