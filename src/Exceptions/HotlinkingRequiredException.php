<?php

declare(strict_types=1);

namespace Gabrielesbaiz\UnsplashToolkit\Exceptions;

/**
 * The Unsplash API Guidelines require the image URLs returned under photo.urls
 * to be hotlinked rather than copied and re-served.
 */
final class HotlinkingRequiredException extends UnsplashException
{
    /**
     * Create a new exception for an attempt to store image bytes locally.
     */
    public static function storageDisabled(): self
    {
        return new self(
            'Downloading Unsplash images to a local disk is disabled. The Unsplash API Guidelines '
            .'state that "all API uses must use the hotlinked image URLs returned by the API under '
            .'the photo.urls properties", so hotlink the photo instead: curate it with '
            .'Unsplash::curate($photo) and render it with <x-unsplash::image>, which keeps the ixid '
            .'parameter intact so views are credited to the photographer. If Unsplash has granted '
            .'you written permission to store copies, enable unsplash-toolkit.compliance.'
            .'allow_local_storage and record the permission in compliance.storage_permission_reference.'
        );
    }

    /**
     * Create a new exception for storage enabled without a recorded permission.
     */
    public static function permissionReferenceMissing(): self
    {
        return new self(
            'unsplash-toolkit.compliance.allow_local_storage is enabled but no '
            .'compliance.storage_permission_reference is set. Storing Unsplash images requires '
            .'written permission from Unsplash: record the ticket or email granting it so the '
            .'exception to the hotlinking guideline stays auditable.'
        );
    }
}
