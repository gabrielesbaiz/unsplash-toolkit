<?php

declare(strict_types=1);

namespace Gabrielesbaiz\UnsplashToolkit\Data;

use ArrayAccess;
use Gabrielesbaiz\UnsplashToolkit\Enums\Size;
use Gabrielesbaiz\UnsplashToolkit\Support\Compliance;
use Gabrielesbaiz\UnsplashToolkit\Support\ImageUrl;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;
use LogicException;
use Stringable;

/**
 * A photo as Unsplash describes it.
 *
 * The URLs held here are Unsplash's own and are meant to be hotlinked: every
 * accessor returns a URL pointing at Unsplash's CDN, resized through its
 * dynamic image parameters, never a copy served from your own disk.
 *
 * @implements Arrayable<string, mixed>
 * @implements ArrayAccess<string, mixed>
 */
final readonly class Photo implements Arrayable, ArrayAccess, Jsonable, JsonSerializable, Stringable
{
    /**
     * Create a new photo.
     *
     * @param  array<string, string>  $urls
     * @param  array<string, string>  $links
     * @param  array<int, string>  $tags
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $id,
        public array $urls,
        public Author $author,
        public string $downloadLocation,
        public int $width = 0,
        public int $height = 0,
        public ?string $color = null,
        public ?string $blurHash = null,
        public ?string $description = null,
        public ?string $altDescription = null,
        public ?string $htmlLink = null,
        public array $links = [],
        public array $tags = [],
        public ?int $likes = null,
        public ?string $createdAt = null,
        public array $raw = [],
    ) {}

    /**
     * Create a photo from an Unsplash API payload.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function fromResponse(array $payload): self
    {
        /** @var array<string, string> $urls */
        $urls = is_array($payload['urls'] ?? null) ? array_map(strval(...), $payload['urls']) : [];

        /** @var array<string, string> $links */
        $links = is_array($payload['links'] ?? null) ? array_map(strval(...), $payload['links']) : [];

        /** @var array<string, mixed> $user */
        $user = is_array($payload['user'] ?? null) ? $payload['user'] : [];

        $tags = [];

        if (is_array($payload['tags'] ?? null)) {
            foreach ($payload['tags'] as $tag) {
                if (is_array($tag) && isset($tag['title'])) {
                    $tags[] = (string) $tag['title'];
                } elseif (is_string($tag)) {
                    $tags[] = $tag;
                }
            }
        }

        return new self(
            id: (string) ($payload['id'] ?? ''),
            urls: $urls,
            author: Author::fromResponse($user),
            downloadLocation: (string) ($links['download_location'] ?? ''),
            width: (int) ($payload['width'] ?? 0),
            height: (int) ($payload['height'] ?? 0),
            color: isset($payload['color']) ? (string) $payload['color'] : null,
            blurHash: isset($payload['blur_hash']) ? (string) $payload['blur_hash'] : null,
            description: isset($payload['description']) ? (string) $payload['description'] : null,
            altDescription: isset($payload['alt_description']) ? (string) $payload['alt_description'] : null,
            htmlLink: $links['html'] ?? null,
            links: $links,
            tags: $tags,
            likes: isset($payload['likes']) ? (int) $payload['likes'] : null,
            createdAt: isset($payload['created_at']) ? (string) $payload['created_at'] : null,
            raw: $payload,
        );
    }

    /**
     * Get the hotlinked URL Unsplash returned for the given size, unmodified.
     */
    public function rawUrl(Size $size = Size::Regular): string
    {
        $urls = $this->urls;

        return $urls[$size->value] ?? $urls['regular'] ?? (reset($urls) ?: '');
    }

    /**
     * Get a hotlinked URL, optionally resized through Unsplash's image parameters.
     *
     * Resizing happens on Unsplash's CDN. The ixid parameter is preserved so the
     * view is still reported to the photographer.
     */
    public function url(
        Size $size = Size::Regular,
        ?int $width = null,
        ?int $height = null,
        ?int $quality = null,
        ?string $format = null,
        ?string $fit = null,
        ?int $dpr = null,
    ): string {
        $url = $this->rawUrl($size);

        if ($width === null && $height === null && $quality === null && $format === null && $fit === null && $dpr === null) {
            return $url;
        }

        return $this->imageUrl()->sized($url, $width, $height, $quality, $format, $fit, $dpr);
    }

    /**
     * Build a responsive srcset for this photo.
     *
     * @param  array<int, int>|null  $widths
     */
    public function srcset(?array $widths = null, bool $preserveAspectRatio = true, ?int $quality = null): string
    {
        return $this->imageUrl()->srcset(
            $this->rawUrl(Size::Raw) !== '' ? $this->rawUrl(Size::Raw) : $this->rawUrl(),
            $widths,
            $preserveAspectRatio ? $this->aspectRatio() : null,
            $quality,
        );
    }

    /**
     * Get the aspect ratio, or null when the dimensions are unknown.
     */
    public function aspectRatio(): ?float
    {
        if ($this->width <= 0 || $this->height <= 0) {
            return null;
        }

        return $this->width / $this->height;
    }

    /**
     * Get the photographer credit for this photo.
     */
    public function attribution(): Attribution
    {
        return new Attribution(
            authorName: $this->author->name,
            authorLink: $this->author->link,
            appName: app(Compliance::class)->attributionName(),
        );
    }

    /**
     * Get the best available alternative text.
     */
    public function alt(): string
    {
        return $this->altDescription
            ?? $this->description
            ?? "Photo by {$this->author->name} on Unsplash";
    }

    /**
     * Get the instance as an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'urls' => $this->urls,
            'download_location' => $this->downloadLocation,
            'width' => $this->width,
            'height' => $this->height,
            'color' => $this->color,
            'blur_hash' => $this->blurHash,
            'description' => $this->description,
            'alt_description' => $this->altDescription,
            'html_link' => $this->htmlLink,
            'tags' => $this->tags,
            'likes' => $this->likes,
            'created_at' => $this->createdAt,
            'author' => $this->author->toArray(),
        ];
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Convert the object to its JSON representation.
     */
    public function toJson($options = 0): string
    {
        return (string) json_encode($this->toArray(), $options | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Determine if the given offset exists.
     */
    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->toArray());
    }

    /**
     * Get the value for a given offset.
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->toArray()[$offset] ?? null;
    }

    /**
     * Set the value at the given offset.
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new LogicException('Photo objects are immutable.');
    }

    /**
     * Unset the value at the given offset.
     */
    public function offsetUnset(mixed $offset): void
    {
        throw new LogicException('Photo objects are immutable.');
    }

    /**
     * Get the hotlinked URL of the photo.
     */
    public function __toString(): string
    {
        return $this->rawUrl();
    }

    /**
     * Resolve the image URL builder.
     */
    private function imageUrl(): ImageUrl
    {
        return app(ImageUrl::class);
    }
}
