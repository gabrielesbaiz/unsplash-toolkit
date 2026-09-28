<?php

declare(strict_types=1);

namespace Gabrielesbaiz\UnsplashToolkit\Enums;

/**
 * The lifecycle of a curated photo.
 *
 * A photo the photographer removes from Unsplash becomes Unavailable, and pool
 * selection skips it: without local copies, this is how a dead hotlink stops
 * being served.
 */
enum AssetStatus: string
{
    case Active = 'active';
    case Unavailable = 'unavailable';
    case Archived = 'archived';

    /**
     * Determine if a photo in this state may be served.
     */
    public function isSelectable(): bool
    {
        return $this === self::Active;
    }

    /**
     * Get a human readable label.
     */
    public function label(): string
    {
        return ucfirst($this->value);
    }
}
