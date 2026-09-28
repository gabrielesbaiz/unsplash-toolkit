<?php

declare(strict_types=1);

namespace Gabrielesbaiz\UnsplashToolkit\Database\Factories;

use Gabrielesbaiz\UnsplashToolkit\Enums\AssetStatus;
use Gabrielesbaiz\UnsplashToolkit\Models\UnsplashAsset;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<UnsplashAsset>
 */
class UnsplashAssetFactory extends Factory
{
    protected $model = UnsplashAsset::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $id = Str::random(11);
        $username = $this->faker->userName();
        $base = "https://images.unsplash.com/photo-{$id}";
        $ixid = 'M3wxMjA3fDB8MXxhbGx8'.Str::random(8);

        return [
            'unsplash_id' => $id,
            'pool' => 'default',
            'status' => AssetStatus::Active,
            'urls' => [
                'raw' => "{$base}?ixid={$ixid}&ixlib=rb-4.0.3",
                'full' => "{$base}?ixid={$ixid}&ixlib=rb-4.0.3&q=85",
                'regular' => "{$base}?ixid={$ixid}&ixlib=rb-4.0.3&w=1080",
                'small' => "{$base}?ixid={$ixid}&ixlib=rb-4.0.3&w=400",
                'thumb' => "{$base}?ixid={$ixid}&ixlib=rb-4.0.3&w=200",
            ],
            'download_location' => "https://api.unsplash.com/photos/{$id}/download?ixid={$ixid}",
            'html_link' => "https://unsplash.com/photos/{$id}",
            'width' => 4000,
            'height' => 2500,
            'color' => '#26260c',
            'blurhash' => 'LFC$yHwc8^$yIAS$%M%00KxukYIp',
            'description' => $this->faker->sentence(),
            'alt_description' => $this->faker->words(3, true),
            'author_name' => $this->faker->name(),
            'author_username' => $username,
            'author_link' => "https://unsplash.com/@{$username}",
            'curated_at' => now(),
        ];
    }

    /**
     * Indicate that the photo is no longer available on Unsplash.
     */
    public function unavailable(): self
    {
        return $this->state(fn (): array => ['status' => AssetStatus::Unavailable]);
    }

    /**
     * Place the photo in the given pool.
     */
    public function pool(string $pool): self
    {
        return $this->state(fn (): array => ['pool' => $pool]);
    }
}
