<?php

declare(strict_types=1);

namespace Gabrielesbaiz\UnsplashToolkit\Data;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * The photographer who took a photo.
 *
 * @implements Arrayable<string, mixed>
 */
final readonly class Author implements Arrayable, JsonSerializable
{
    /**
     * Create a new author.
     */
    public function __construct(
        public string $id,
        public string $username,
        public string $name,
        public string $link,
        public ?string $portfolioUrl = null,
        public ?string $bio = null,
        public ?string $location = null,
        public ?string $profileImage = null,
    ) {}

    /**
     * Create an author from an Unsplash API payload.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function fromResponse(array $payload): self
    {
        /** @var array<string, mixed> $links */
        $links = is_array($payload['links'] ?? null) ? $payload['links'] : [];

        /** @var array<string, mixed> $profileImage */
        $profileImage = is_array($payload['profile_image'] ?? null) ? $payload['profile_image'] : [];

        $username = (string) ($payload['username'] ?? '');

        return new self(
            id: (string) ($payload['id'] ?? ''),
            username: $username,
            name: (string) ($payload['name'] ?? $username),
            link: (string) ($links['html'] ?? ($username !== '' ? "https://unsplash.com/@{$username}" : 'https://unsplash.com')),
            portfolioUrl: isset($payload['portfolio_url']) ? (string) $payload['portfolio_url'] : null,
            bio: isset($payload['bio']) ? (string) $payload['bio'] : null,
            location: isset($payload['location']) ? (string) $payload['location'] : null,
            profileImage: isset($profileImage['medium']) ? (string) $profileImage['medium'] : null,
        );
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
            'username' => $this->username,
            'name' => $this->name,
            'link' => $this->link,
            'portfolio_url' => $this->portfolioUrl,
            'bio' => $this->bio,
            'location' => $this->location,
            'profile_image' => $this->profileImage,
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
}
