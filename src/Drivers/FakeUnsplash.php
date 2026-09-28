<?php

declare(strict_types=1);

namespace Gabrielesbaiz\UnsplashToolkit\Drivers;

use Gabrielesbaiz\UnsplashToolkit\Contracts\UnsplashClient;
use Gabrielesbaiz\UnsplashToolkit\Data\RateLimit;
use Gabrielesbaiz\UnsplashToolkit\Exceptions\PhotoNotFoundException;
use Illuminate\Support\Str;

/**
 * An in-memory Unsplash client for tests.
 *
 * Records every request so assertions can check that a download event fired,
 * and serves deterministic payloads shaped like real Unsplash responses.
 */
final class FakeUnsplash implements UnsplashClient
{
    /** @var array<int, array{endpoint: string, query: array<string, mixed>}> */
    public array $requests = [];

    /** @var array<string, array<mixed>> */
    private array $responses = [];

    /** @var array<int, string> */
    private array $missing = [];

    /**
     * Create a new fake client.
     */
    public function __construct(private ?RateLimit $rateLimit = null)
    {
        $this->rateLimit ??= new RateLimit(limit: 50, remaining: 49);
    }

    /**
     * Queue a canned response for an endpoint.
     *
     * @param  array<mixed>  $payload
     */
    public function stub(string $endpoint, array $payload): self
    {
        $this->responses[ltrim($endpoint, '/')] = $payload;

        return $this;
    }

    /**
     * Mark a photo id as removed from Unsplash.
     */
    public function missing(string $id): self
    {
        $this->missing[] = $id;

        return $this;
    }

    /**
     * Send a GET request to the given Unsplash endpoint.
     *
     * @param  array<string, mixed>  $query
     * @return array<mixed>
     */
    public function get(string $endpoint, array $query = []): array
    {
        $endpoint = ltrim($endpoint, '/');

        $this->requests[] = ['endpoint' => $endpoint, 'query' => $query];

        foreach ($this->missing as $id) {
            if ($endpoint === "photos/{$id}") {
                throw PhotoNotFoundException::make($id);
            }
        }

        if (isset($this->responses[$endpoint])) {
            return $this->responses[$endpoint];
        }

        return $this->generate($endpoint, $query);
    }

    /**
     * Request an absolute URL Unsplash supplied.
     *
     * @return array<mixed>
     */
    public function getAbsolute(string $url): array
    {
        $this->requests[] = ['endpoint' => $url, 'query' => []];

        return ['url' => 'https://images.unsplash.com/photo-fake?ixid=fake-ixid'];
    }

    /**
     * Send several GET requests concurrently.
     *
     * @param  array<string, array{endpoint: string, query?: array<string, mixed>}>  $requests
     * @return array<string, array<mixed>|null>
     */
    public function pool(array $requests): array
    {
        $results = [];

        foreach ($requests as $name => $request) {
            try {
                $results[$name] = $this->get($request['endpoint'], $request['query'] ?? []);
            } catch (PhotoNotFoundException) {
                $results[$name] = null;
            }
        }

        return $results;
    }

    /**
     * Get the rate limit reported on the most recent response.
     */
    public function rateLimit(): ?RateLimit
    {
        return $this->rateLimit;
    }

    /**
     * Determine if a request was sent to the given endpoint.
     */
    public function sentRequestTo(string $endpoint): bool
    {
        foreach ($this->requests as $request) {
            if (str_contains($request['endpoint'], $endpoint)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the number of requests sent.
     */
    public function requestCount(): int
    {
        return count($this->requests);
    }

    /**
     * Build a photo payload shaped like a real Unsplash response.
     *
     * @return array<string, mixed>
     */
    public static function photoPayload(string $id = 'Dwu85P9SOIk', string $username = 'ashim'): array
    {
        $base = "https://images.unsplash.com/photo-{$id}";
        $ixid = 'M3wxMjA3fDB8MXxhbGx8fHx8fHx8fHwx';

        return [
            'id' => $id,
            'width' => 4000,
            'height' => 2500,
            'color' => '#26260c',
            'blur_hash' => 'LFC$yHwc8^$yIAS$%M%00KxukYIp',
            'description' => 'A fake photo for tests',
            'alt_description' => 'fake photo',
            'created_at' => '2024-01-01T00:00:00Z',
            'likes' => 42,
            'urls' => [
                'raw' => "{$base}?ixid={$ixid}&ixlib=rb-4.0.3",
                'full' => "{$base}?ixid={$ixid}&ixlib=rb-4.0.3&q=85",
                'regular' => "{$base}?ixid={$ixid}&ixlib=rb-4.0.3&w=1080",
                'small' => "{$base}?ixid={$ixid}&ixlib=rb-4.0.3&w=400",
                'thumb' => "{$base}?ixid={$ixid}&ixlib=rb-4.0.3&w=200",
            ],
            'links' => [
                'self' => "https://api.unsplash.com/photos/{$id}",
                'html' => "https://unsplash.com/photos/{$id}",
                'download' => "https://unsplash.com/photos/{$id}/download",
                'download_location' => "https://api.unsplash.com/photos/{$id}/download?ixid={$ixid}",
            ],
            'user' => [
                'id' => 'user-'.$username,
                'username' => $username,
                'name' => Str::title($username).' Photographer',
                'portfolio_url' => null,
                'bio' => null,
                'location' => null,
                'links' => [
                    'html' => "https://unsplash.com/@{$username}",
                ],
                'profile_image' => [
                    'medium' => "https://images.unsplash.com/profile-{$username}",
                ],
            ],
            'tags' => [
                ['title' => 'car'],
                ['title' => 'road'],
            ],
        ];
    }

    /**
     * Generate a plausible payload for an endpoint that was not stubbed.
     *
     * @param  array<string, mixed>  $query
     * @return array<mixed>
     */
    private function generate(string $endpoint, array $query): array
    {
        if ($endpoint === 'search/photos') {
            $results = [
                self::photoPayload('aaa1111aaaa'),
                self::photoPayload('bbb2222bbbb', 'mila'),
            ];

            return ['total' => 2, 'total_pages' => 1, 'results' => $results];
        }

        if ($endpoint === 'photos/random') {
            $count = (int) ($query['count'] ?? 0);

            if ($count > 0) {
                return array_map(
                    static fn (int $index): array => self::photoPayload('rnd'.str_pad((string) $index, 8, '0', STR_PAD_LEFT)),
                    range(1, $count),
                );
            }

            return self::photoPayload('rnd00000001');
        }

        if (str_starts_with($endpoint, 'photos/')) {
            return self::photoPayload(substr($endpoint, 7));
        }

        if (str_starts_with($endpoint, 'collections/')) {
            $segments = explode('/', $endpoint);

            if (($segments[2] ?? null) === 'photos') {
                return [self::photoPayload('col1111aaaa'), self::photoPayload('col2222bbbb', 'mila')];
            }

            return ['id' => $segments[1] ?? '', 'title' => 'Fake collection', 'total_photos' => 2];
        }

        if ($endpoint === 'stats/total') {
            return ['photos' => 100, 'downloads' => 200, 'views' => 300, 'photographers' => 40];
        }

        return [];
    }
}
