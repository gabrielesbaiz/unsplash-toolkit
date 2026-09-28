<?php

declare(strict_types=1);

namespace Gabrielesbaiz\UnsplashToolkit\Drivers;

use Gabrielesbaiz\UnsplashToolkit\Contracts\UnsplashClient;
use Gabrielesbaiz\UnsplashToolkit\Data\RateLimit;
use Gabrielesbaiz\UnsplashToolkit\Exceptions\PhotoNotFoundException;
use Gabrielesbaiz\UnsplashToolkit\Exceptions\RateLimitExceededException;
use Gabrielesbaiz\UnsplashToolkit\Exceptions\UnsplashException;
use Gabrielesbaiz\UnsplashToolkit\Support\Compliance;
use Gabrielesbaiz\UnsplashToolkit\Support\Config;
use Gabrielesbaiz\UnsplashToolkit\Support\ResponseCache;
use Gabrielesbaiz\UnsplashToolkit\Support\Throttle;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest as HttpPendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Throwable;

/**
 * Talks to the Unsplash API over Laravel's HTTP client.
 *
 * Using the framework client rather than a bare Guzzle instance buys timeouts,
 * retries with backoff, concurrent pools and Http::fake() in tests for free.
 */
final class UnsplashApi implements UnsplashClient
{
    private ?RateLimit $rateLimit = null;

    /**
     * Create a new Unsplash API driver.
     */
    public function __construct(
        private readonly Config $config,
        private readonly Compliance $compliance,
        private readonly HttpFactory $http,
        private readonly ResponseCache $cache,
        private readonly Throttle $throttle,
    ) {}

    /**
     * Send a GET request to the given Unsplash endpoint.
     *
     * @param  array<string, mixed>  $query
     * @return array<mixed>
     */
    public function get(string $endpoint, array $query = []): array
    {
        $query = $this->clean($query);

        if (($cached = $this->cache->get($endpoint, $query)) !== null) {
            return $cached;
        }

        $this->throttle->hit();

        $response = $this->request()->get($this->url($endpoint), $query);

        $payload = $this->decode($response, $endpoint);

        $this->cache->put($endpoint, $query, $payload);

        return $payload;
    }

    /**
     * Request an absolute URL Unsplash supplied, such as a download location.
     *
     * These are never cached: a download event must actually reach Unsplash.
     *
     * @return array<mixed>
     */
    public function getAbsolute(string $url): array
    {
        $this->throttle->hit();

        return $this->decode($this->request()->get($url), $url);
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
        $pending = [];

        // Serve whatever is already cached without spending a request.
        foreach ($requests as $name => $request) {
            $query = $this->clean($request['query'] ?? []);
            $cached = $this->cache->get($request['endpoint'], $query);

            if ($cached !== null) {
                $results[$name] = $cached;

                continue;
            }

            $pending[$name] = ['endpoint' => $request['endpoint'], 'query' => $query];
        }

        foreach (array_chunk($pending, $this->config->concurrency(), true) as $chunk) {
            $this->throttle->hit(count($chunk));

            /** @var array<string, Response|RequestException> $responses */
            $responses = $this->http->pool(function (Pool $pool) use ($chunk): array {
                return array_map(
                    fn (array $request, string $name) => $this
                        ->configure($pool->as($name))
                        ->get($this->url($request['endpoint']), $request['query']),
                    array_values($chunk),
                    array_keys($chunk),
                );
            });

            foreach ($chunk as $name => $request) {
                $response = $responses[$name] ?? null;

                if (! $response instanceof Response) {
                    $results[$name] = null;

                    continue;
                }

                try {
                    $payload = $this->decode($response, $request['endpoint']);
                } catch (UnsplashException) {
                    $results[$name] = null;

                    continue;
                }

                $this->cache->put($request['endpoint'], $request['query'], $payload);

                $results[$name] = $payload;
            }
        }

        return $results;
    }

    /**
     * Get the rate limit Unsplash reported on the most recent response.
     */
    public function rateLimit(): ?RateLimit
    {
        return $this->rateLimit;
    }

    /**
     * Build a configured pending request.
     */
    private function request(): HttpPendingRequest
    {
        return $this->configure($this->http->asJson());
    }

    /**
     * Apply credentials, headers, timeouts and retries to a request.
     *
     * @template TRequest of HttpPendingRequest|Pool
     *
     * @param  TRequest  $request
     * @return TRequest
     */
    private function configure(mixed $request): mixed
    {
        $retry = $this->config->retry();

        return $request
            ->withHeaders([
                'Authorization' => 'Client-ID '.$this->compliance->accessKey(),
                'Accept-Version' => $this->config->apiVersion(),
                'Accept' => 'application/json',
            ])
            ->timeout($this->config->timeout())
            ->connectTimeout($this->config->connectTimeout())
            ->retry(
                max(1, $retry['times']),
                $retry['backoff']
                    ? fn (int $attempt): int => $retry['sleep'] * (2 ** ($attempt - 1))
                    : $retry['sleep'],
                fn (Throwable $exception): bool => $this->shouldRetry($exception),
                throw: false,
            );
    }

    /**
     * Determine if a failed request is worth retrying.
     *
     * A rate limited response is never retried: the budget is already spent, and
     * hammering it is what gets an application's access turned off.
     */
    private function shouldRetry(Throwable $exception): bool
    {
        if ($exception instanceof ConnectionException) {
            return true;
        }

        if (! $exception instanceof RequestException) {
            return false;
        }

        if ($this->isRateLimited($exception->response)) {
            return false;
        }

        return $exception->response->status() >= 500;
    }

    /**
     * Determine if a response represents an exhausted rate limit.
     */
    private function isRateLimited(Response $response): bool
    {
        if ($response->status() === 429) {
            return true;
        }

        $remaining = $response->header('X-Ratelimit-Remaining');

        return $remaining !== '' && (int) $remaining <= 0;
    }

    /**
     * Turn a response into a decoded payload, translating failures.
     *
     * @return array<mixed>
     */
    private function decode(Response $response, string $context): array
    {
        $this->rateLimit = RateLimit::fromHeaders($response->headers());

        $this->throttle->remember($this->rateLimit);

        if ($this->isRateLimited($response)) {
            $retryAfter = $response->header('Retry-After');

            throw RateLimitExceededException::make($retryAfter !== '' ? (int) $retryAfter : null);
        }

        if ($response->status() === 404) {
            throw PhotoNotFoundException::make($context);
        }

        if ($response->failed()) {
            throw new UnsplashException(sprintf(
                'The Unsplash API request to [%s] failed with status %d: %s',
                $this->compliance->redact($context),
                $response->status(),
                $this->compliance->redact(mb_substr($response->body(), 0, 500)),
            ));
        }

        $decoded = $response->json();

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Build an absolute endpoint URL.
     */
    private function url(string $endpoint): string
    {
        if (str_starts_with($endpoint, 'http://') || str_starts_with($endpoint, 'https://')) {
            return $endpoint;
        }

        return $this->config->baseUrl().'/'.ltrim($endpoint, '/');
    }

    /**
     * Drop null and empty query parameters.
     *
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    private function clean(array $query): array
    {
        return array_filter($query, static fn (mixed $value): bool => $value !== null && $value !== '');
    }
}
