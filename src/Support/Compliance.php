<?php

declare(strict_types=1);

namespace Gabrielesbaiz\UnsplashToolkit\Support;

use Gabrielesbaiz\UnsplashToolkit\Exceptions\HotlinkingRequiredException;
use Gabrielesbaiz\UnsplashToolkit\Exceptions\MissingAccessKeyException;
use Gabrielesbaiz\UnsplashToolkit\Exceptions\MissingAttributionNameException;

/**
 * The single place the Unsplash API Guidelines are enforced.
 *
 * Every rule routes through here so the package's compliance posture can be
 * audited by reading one class, and so "unsplash:doctor" and the runtime share
 * exactly the same checks.
 *
 * @see https://help.unsplash.com/api-guidelines
 */
final readonly class Compliance
{
    /**
     * Create a new compliance guard.
     */
    public function __construct(private Config $config) {}

    /**
     * Get the access key, failing when none is configured.
     */
    public function accessKey(): string
    {
        return $this->config->accessKey() ?? throw MissingAccessKeyException::make();
    }

    /**
     * Get the utm_source every attribution link carries.
     *
     * The guidelines require attributing the photographer and Unsplash with
     * links back to their profiles, tagged with your application name. A
     * missing name would silently produce a credit that does not qualify, so
     * this throws instead.
     */
    public function attributionName(): string
    {
        return $this->config->appName() ?? throw MissingAttributionNameException::make();
    }

    /**
     * Determine if an attribution name is configured, without throwing.
     */
    public function hasAttributionName(): bool
    {
        return $this->config->appName() !== null;
    }

    /**
     * Assert that writing image bytes to a local disk is permitted.
     *
     * Hotlinking is the compliant default. Storing copies is only permissible
     * with written permission from Unsplash, which must be recorded in config
     * so the exception stays auditable.
     */
    public function assertLocalStorageAllowed(): void
    {
        if (! $this->config->allowsLocalStorage()) {
            throw HotlinkingRequiredException::storageDisabled();
        }

        if ($this->config->storagePermissionReference() === null) {
            throw HotlinkingRequiredException::permissionReferenceMissing();
        }
    }

    /**
     * Determine if local storage is permitted, without throwing.
     */
    public function allowsLocalStorage(): bool
    {
        return $this->config->allowsLocalStorage()
            && $this->config->storagePermissionReference() !== null;
    }

    /**
     * Redact credentials from a string before it reaches a log or an exception.
     */
    public function redact(string $value): string
    {
        $secrets = array_filter([
            $this->config->accessKey(),
            $this->config->secretKey(),
        ]);

        foreach ($secrets as $secret) {
            $value = str_replace($secret, '[redacted]', $value);
        }

        return $value;
    }

    /**
     * Mask a credential for display, keeping just enough to identify it.
     */
    public function mask(?string $secret): string
    {
        if ($secret === null || $secret === '') {
            return 'not set';
        }

        return mb_substr($secret, 0, 4).str_repeat('*', 8).mb_substr($secret, -2);
    }
}
