<?php

declare(strict_types=1);

namespace Gabrielesbaiz\UnsplashToolkit\Data;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use JsonSerializable;
use Stringable;

/**
 * The photographer credit the API Guidelines require next to every photo.
 *
 * Author names and profile links come from a third party, so every value is
 * escaped before it reaches the page. Links carry the utm_source and utm_medium
 * parameters Unsplash asks for, and the query string is merged rather than
 * appended with a bare "?" so a link that already has one stays valid.
 *
 * @implements Arrayable<string, string>
 */
final readonly class Attribution implements Arrayable, Htmlable, JsonSerializable, Stringable
{
    /**
     * Create a new attribution.
     */
    public function __construct(
        public string $authorName,
        public string $authorLink,
        public string $appName,
        public string $unsplashUrl = 'https://unsplash.com',
    ) {}

    /**
     * Get the photographer profile link, tagged for Unsplash referral tracking.
     */
    public function authorUrl(): string
    {
        return $this->tag($this->authorLink);
    }

    /**
     * Get the Unsplash link, tagged for referral tracking.
     */
    public function unsplashUrl(): string
    {
        return $this->tag($this->unsplashUrl);
    }

    /**
     * Get the credit as plain text.
     */
    public function toText(): string
    {
        return "Photo by {$this->authorName} on Unsplash";
    }

    /**
     * Get the credit as HTML.
     */
    public function toHtml(): string
    {
        $author = e($this->authorName);
        $authorUrl = e($this->authorUrl());
        $unsplashUrl = e($this->unsplashUrl());

        return sprintf(
            'Photo by <a href="%s" target="_blank" rel="noopener noreferrer">%s</a> on '
            .'<a href="%s" target="_blank" rel="noopener noreferrer">Unsplash</a>',
            $authorUrl,
            $author,
            $unsplashUrl,
        );
    }

    /**
     * Get the credit as an escaped HTML string.
     */
    public function toHtmlString(): HtmlString
    {
        return new HtmlString($this->toHtml());
    }

    /**
     * Add the referral parameters to a link, preserving any it already carries.
     */
    private function tag(string $url): string
    {
        if ($url === '') {
            return $url;
        }

        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['host'])) {
            return $url;
        }

        parse_str($parts['query'] ?? '', $query);

        /** @var array<string, mixed> $query */
        $query['utm_source'] = $this->appName;
        $query['utm_medium'] = 'referral';

        $rebuilt = ($parts['scheme'] ?? 'https').'://'.$parts['host'].($parts['path'] ?? '');

        return $rebuilt.'?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Get the instance as an array.
     *
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'author_name' => $this->authorName,
            'author_url' => $this->authorUrl(),
            'unsplash_url' => $this->unsplashUrl(),
            'text' => $this->toText(),
            'html' => $this->toHtml(),
        ];
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array<string, string>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Get the credit as HTML.
     */
    public function __toString(): string
    {
        return $this->toHtml();
    }
}
