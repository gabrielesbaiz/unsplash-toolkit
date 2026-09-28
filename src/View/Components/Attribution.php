<?php

declare(strict_types=1);

namespace Gabrielesbaiz\UnsplashToolkit\View\Components;

use Gabrielesbaiz\UnsplashToolkit\Data\Attribution as AttributionData;
use Gabrielesbaiz\UnsplashToolkit\Data\Photo;
use Gabrielesbaiz\UnsplashToolkit\Models\UnsplashAsset;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Renders the photographer credit the API Guidelines require.
 *
 * Accepts a null photo and renders nothing, so a page whose pool is empty
 * degrades quietly instead of failing on a missing model.
 */
class Attribution extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public UnsplashAsset|Photo|null $asset = null,
    ) {}

    /**
     * Get the credit, if there is a photo to credit.
     */
    public function credit(): ?AttributionData
    {
        return $this->asset?->attribution();
    }

    /**
     * Get the view that represents the component.
     */
    public function render(): View
    {
        /** @var view-string $view */
        $view = 'unsplash::components.attribution';

        return view($view);
    }
}
