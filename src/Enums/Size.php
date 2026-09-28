<?php

declare(strict_types=1);

namespace Gabrielesbaiz\UnsplashToolkit\Enums;

/**
 * The image sizes Unsplash returns under photo.urls.
 */
enum Size: string
{
    case Raw = 'raw';
    case Full = 'full';
    case Regular = 'regular';
    case Small = 'small';
    case Thumb = 'thumb';

    /**
     * Get the approximate width Unsplash renders this size at, if it is fixed.
     */
    public function width(): ?int
    {
        return match ($this) {
            self::Raw, self::Full => null,
            self::Regular => 1080,
            self::Small => 400,
            self::Thumb => 200,
        };
    }
}
