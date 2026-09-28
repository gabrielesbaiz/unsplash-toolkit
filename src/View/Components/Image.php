<?php

declare(strict_types=1);

namespace Gabrielesbaiz\UnsplashToolkit\View\Components;

use Gabrielesbaiz\UnsplashToolkit\Data\Attribution as AttributionData;
use Gabrielesbaiz\UnsplashToolkit\Data\Photo;
use Gabrielesbaiz\UnsplashToolkit\Enums\Size;
use Gabrielesbaiz\UnsplashToolkit\Models\UnsplashAsset;
use Gabrielesbaiz\UnsplashToolkit\Support\Config;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Renders a hotlinked Unsplash photo.
 *
 * The src and srcset point at Unsplash's CDN with the ixid preserved, and the
 * photographer credit is rendered alongside unless it is explicitly suppressed,
 * so the default output satisfies the attribution guideline.
 */
class Image extends Component
{
    /**
     * Create a new component instance.
     *
     * @param  array<int, int>|null  $sizes
     */
    public function __construct(
        public UnsplashAsset|Photo|null $photo = null,
        public ?array $sizes = null,
        public ?int $width = null,
        public ?int $height = null,
        public ?int $quality = null,
        public ?string $alt = null,
        public bool $attribution = true,
        public ?bool $lazy = null,
        public string $sizesAttribute = '100vw',
    ) {}

    /**
     * Get the hotlinked source URL.
     */
    public function src(): string
    {
        if ($this->photo === null) {
            return '';
        }

        return $this->photo->url(
            Size::Regular,
            width: $this->width,
            height: $this->height,
            quality: $this->quality,
        );
    }

    /**
     * Get the responsive srcset.
     */
    public function srcset(): string
    {
        if ($this->photo === null) {
            return '';
        }

        return $this->photo->srcset($this->sizes, quality: $this->quality);
    }

    /**
     * Get the alternative text.
     */
    public function altText(): string
    {
        return $this->alt ?? $this->photo?->alt() ?? '';
    }

    /**
     * Get the colour painted while the image loads.
     */
    public function placeholder(): string
    {
        $config = app(Config::class);

        if ($this->photo instanceof UnsplashAsset) {
            return $this->photo->placeholderColor();
        }

        if ($this->photo === null) {
            return $config->fallbackColor();
        }

        return $this->photo->color ?? $config->fallbackColor();
    }

    /**
     * Get the photographer credit.
     */
    public function credit(): ?AttributionData
    {
        if (! $this->attribution || $this->photo === null) {
            return null;
        }

        return $this->photo->attribution();
    }

    /**
     * Determine if the image is lazily loaded.
     */
    public function isLazy(): bool
    {
        return $this->lazy ?? app(Config::class)->lazyImages();
    }

    /**
     * Get the view that represents the component.
     */
    public function render(): View
    {
        /** @var view-string $view */
        $view = 'unsplash::components.image';

        return view($view);
    }
}
