<?php

declare(strict_types=1);

namespace Gabrielesbaiz\UnsplashToolkit\Support;

use Gabrielesbaiz\UnsplashToolkit\Data\Photo;
use Gabrielesbaiz\UnsplashToolkit\Enums\Size;
use Gabrielesbaiz\UnsplashToolkit\Exceptions\DownloadFailedException;
use Gabrielesbaiz\UnsplashToolkit\Models\UnsplashAsset;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Writes a photo's bytes to a local disk.
 *
 * This is the gated path: hotlinking is the compliant default, and this class
 * runs only when Unsplash has granted written permission to store copies.
 *
 * The transfer is streamed rather than buffered, so a full-resolution photo does
 * not have to fit in PHP's memory limit, and the file name is derived from the
 * photo id and size rather than probed against the disk in a loop.
 */
final readonly class Downloader
{
    /**
     * Create a new downloader.
     */
    public function __construct(
        private Config $config,
        private Compliance $compliance,
        private HttpFactory $http,
    ) {}

    /**
     * Download a photo and record the file on its registry entry.
     */
    public function store(Photo $photo, UnsplashAsset $asset, Size $size = Size::Regular): UnsplashAsset
    {
        $this->compliance->assertLocalStorageAllowed();

        $url = $photo->rawUrl($size);

        if ($url === '') {
            throw DownloadFailedException::make($photo->id, "no URL is available for the {$size->value} size");
        }

        $disk = $this->config->storageDisk();
        $temporary = tempnam(sys_get_temp_dir(), 'unsplash');

        if ($temporary === false) {
            throw DownloadFailedException::make($photo->id, 'a temporary file could not be created');
        }

        try {
            $response = $this->http
                ->timeout($this->config->timeout())
                ->connectTimeout($this->config->connectTimeout())
                ->sink($temporary)
                ->get($url);

            if ($response->failed()) {
                throw DownloadFailedException::make($photo->id, "the CDN responded with status {$response->status()}");
            }

            $mime = $response->header('Content-Type') ?: 'image/jpeg';
            $path = $this->path($photo, $size, $mime);

            $stream = fopen($temporary, 'rb');

            if ($stream === false) {
                throw DownloadFailedException::make($photo->id, 'the downloaded file could not be read');
            }

            try {
                Storage::disk($disk)->writeStream($path, $stream);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            $asset->forceFill([
                'disk' => $disk,
                'path' => $path,
                'mime' => $mime,
                'size' => filesize($temporary) ?: null,
            ])->save();

            return $asset;
        } catch (DownloadFailedException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw DownloadFailedException::make($photo->id, $this->compliance->redact($exception->getMessage()));
        } finally {
            if (is_file($temporary)) {
                @unlink($temporary);
            }
        }
    }

    /**
     * Build a content addressed path for a photo.
     *
     * Deriving the name from the photo id and size makes the write idempotent,
     * which removes the collision probe the previous implementation ran against
     * the disk on every import.
     */
    private function path(Photo $photo, Size $size, string $mime): string
    {
        $extension = match (true) {
            str_contains($mime, 'png') => 'png',
            str_contains($mime, 'webp') => 'webp',
            str_contains($mime, 'avif') => 'avif',
            str_contains($mime, 'gif') => 'gif',
            default => 'jpg',
        };

        $directory = $this->config->storagePath();
        $name = sha1($photo->id.'|'.$size->value);

        return trim("{$directory}/{$name}.{$extension}", '/');
    }
}
