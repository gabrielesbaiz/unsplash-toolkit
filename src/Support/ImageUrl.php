<?php

declare(strict_types=1);

namespace Gabrielesbaiz\UnsplashToolkit\Support;

/**
 * Builds hotlinked Unsplash image URLs.
 *
 * Unsplash serves images through imgix, so a photo is resized by adding query
 * parameters to the URL it already gave us rather than by downloading and
 * re-encoding the file.
 *
 * The ixid parameter is what reports a photo view back to the photographer, and
 * the API Guidelines require every manipulation to keep it. This class rebuilds
 * a URL by merging into its existing query string, so ixid and ixlib survive by
 * construction instead of by remembering to copy them.
 */
final readonly class ImageUrl
{
    /**
     * Parameters that must never be dropped when a URL is rewritten.
     */
    public const PRESERVED = ['ixid', 'ixlib'];

    /**
     * Create a new image URL builder.
     */
    public function __construct(private Config $config) {}

    /**
     * Apply the given dynamic image parameters to a hotlinked Unsplash URL.
     *
     * @param  array<string, int|string|null>  $params
     */
    public function build(string $url, array $params = []): string
    {
        if ($url === '') {
            return $url;
        }

        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['host'])) {
            return $url;
        }

        parse_str($parts['query'] ?? '', $existing);

        /** @var array<string, mixed> $existing */
        $merged = $existing;

        foreach ($params as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $merged[$key] = $value;
        }

        // Defend the guideline even if a caller passes ixid explicitly.
        foreach (self::PRESERVED as $key) {
            if (isset($existing[$key])) {
                $merged[$key] = $existing[$key];
            }
        }

        $query = http_build_query($merged, '', '&', PHP_QUERY_RFC3986);

        $rebuilt = ($parts['scheme'] ?? 'https').'://'.$parts['host'];

        if (isset($parts['port'])) {
            $rebuilt .= ':'.$parts['port'];
        }

        $rebuilt .= $parts['path'] ?? '';

        return $query === '' ? $rebuilt : $rebuilt.'?'.$query;
    }

    /**
     * Apply the configured defaults plus the given dimensions to a URL.
     */
    public function sized(
        string $url,
        ?int $width = null,
        ?int $height = null,
        ?int $quality = null,
        ?string $format = null,
        ?string $fit = null,
        ?int $dpr = null,
    ): string {
        return $this->build($url, [
            'w' => $width,
            'h' => $height,
            'q' => $quality ?? $this->config->imageQuality(),
            'fm' => $format ?? $this->config->imageFormat(),
            'fit' => $fit ?? $this->config->imageFit(),
            'dpr' => $dpr,
        ]);
    }

    /**
     * Build a responsive srcset for the given widths.
     *
     * When an aspect ratio is given each entry is cropped to it, so the browser
     * never reflows as a larger candidate loads.
     *
     * @param  array<int, int>|null  $widths
     */
    public function srcset(string $url, ?array $widths = null, ?float $aspectRatio = null, ?int $quality = null): string
    {
        $widths = $widths ?? $this->config->srcsetWidths();

        $widths = array_values(array_unique(array_filter($widths, fn (int $width): bool => $width > 0)));

        sort($widths);

        $entries = [];

        foreach ($widths as $width) {
            $height = $aspectRatio !== null && $aspectRatio > 0
                ? (int) round($width / $aspectRatio)
                : null;

            $entries[] = $this->sized($url, width: $width, height: $height, quality: $quality)." {$width}w";
        }

        return implode(', ', $entries);
    }

    /**
     * Determine if a URL still carries the tracking parameter.
     */
    public function hasTrackingParameter(string $url): bool
    {
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        return isset($query['ixid']) && $query['ixid'] !== '';
    }
}
