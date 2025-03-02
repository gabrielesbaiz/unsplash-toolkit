<?php

namespace Gabrielesbaiz\UnsplashToolkit;

use Exception;
use Illuminate\Support\Str;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Gabrielesbaiz\UnsplashToolkit\API\UnsplashAPI;
use Gabrielesbaiz\UnsplashToolkit\Http\HttpClient;
use Gabrielesbaiz\UnsplashToolkit\Models\UnsplashAsset;

class UnsplashToolkit extends HttpClient
{
    use UnsplashAPI;

    /**
     * Accepted URL keys from response.
     */
    const PHOTO_KEYS = [
        'raw',
        'full',
        'regular',
        'small',
        'thumb',
    ];

    /**
     * Storage disk to store photos.
     */
    protected $storage;

    /**
     * Guzzle response.
     */
    protected Response $response;

    /**
     * Whether to store the asset in the database.
     */
    protected bool $storeInDatabase;

    /**
     * Creates a new instance of UnsplashToolkit.
     *
     * @return $this
     */
    public function __construct()
    {
        parent::__construct();

        $this->initalizeConfiguration();

        return $this;
    }

    /**
     * Returns the full HTTP response.
     *
     * @return Response
     */
    public function get(): Response
    {
        $this->buildResponse();

        return $this->response;
    }

    /**
     * Returns the HTTP response body as a decoded object.
     *
     * @return object
     */
    public function toJson(): object
    {
        $this->buildResponse();

        return json_decode($this->response->getBody()->getContents());
    }

    /**
     * Returns the HTTP response body as an associative array.
     *
     * @return array
     */
    public function toArray(): array
    {
        $this->buildResponse();

        return json_decode($this->response->getBody()->getContents(), true);
    }

    /**
     * Returns the HTTP response body as a collection.
     *
     * @return Collection
     */
    public function toCollection(): Collection
    {
        $this->buildResponse();

        return collect(json_decode($this->response->getBody()->getContents(), true));
    }

    /**
     * Stores the retrieved photo in the storage.
     *
     * @param string|null $name if no name is provided, a random 24 character name will be generated
     * @param string      $key  defines the size of the retrieving photo
     *
     * @return string    the stored photo name
     * @throws Exception
     */
    public function store(?string $name = null, string $key = 'small'): string
    {
        $response = $this->toArray();

        if (! array_key_exists('urls', $response)) {
            throw new Exception('Photo cannot be stored. The "urls" key is missing, or you are trying to store multiple photos.');
        }

        if (! in_array($key, self::PHOTO_KEYS)) {
            throw new Exception("The provided key \"{$key}\" is an undefined accessor.");
        }

        $name ??= Str::random(24);

        $image = file_get_contents($response['urls'][$key]);

        while ($this->storage->exists("{$name}.jpg")) {
            $name = Str::random(24);
        }

        $this->storage->put("{$name}.jpg", $image);

        if ($this->storeInDatabase) {
            return UnsplashAsset::create([
                'unsplash_id' => $response['id'],
                'name' => "{$name}.jpg",
                'author' => $response['user']['name'],
                'author_link' => $response['user']['links']['html'],
            ]);
        }

        return $name;
    }

    /**
     * Builds the HTTP request.
     *
     * @return $this
     */
    protected function buildResponse(): self
    {
        $verb = $this->apiCall['verb'] ?? 'get';

        $this->response = $this->client->{$verb}("{$this->apiCall['endpoint']}?{$this->getQuery()}");

        return $this;
    }

    /**
     * Initializes storage.
     *
     * @return $this
     */
    private function initalizeConfiguration(): self
    {
        $this->storage = Storage::disk(config('unsplash.disk', 'local'));

        $this->storeInDatabase = config('unsplash.store_in_database', false);

        return $this;
    }

    /**
     * Checks if the accessed property exists as a method.
     * If exists, calls the method and returns the complete response.
     */
    public function __get(string $param)
    {
        if (method_exists($this, $param)) {
            return $this->{$param}()->get();
        }
    }
}
