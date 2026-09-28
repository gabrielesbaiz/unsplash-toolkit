<?php

declare(strict_types=1);

namespace Gabrielesbaiz\UnsplashToolkit\Http\Controllers;

use Gabrielesbaiz\UnsplashToolkit\Data\Photo;
use Gabrielesbaiz\UnsplashToolkit\Data\SearchResult;
use Gabrielesbaiz\UnsplashToolkit\Enums\Color;
use Gabrielesbaiz\UnsplashToolkit\Enums\Orientation;
use Gabrielesbaiz\UnsplashToolkit\Facades\Unsplash;
use Gabrielesbaiz\UnsplashToolkit\Models\UnsplashAsset;
use Gabrielesbaiz\UnsplashToolkit\Support\Config;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Backs the admin picker.
 *
 * The search runs here rather than in the browser so the access key stays
 * server side, as the guidelines require. It returns only what a picker needs
 * to render thumbnails and let someone choose, not a browsable gallery.
 */
class PickerController
{
    /**
     * Search Unsplash on behalf of the picker.
     */
    public function search(Request $request, Config $config): JsonResponse
    {
        $validated = $request->validate([
            'query' => ['required', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:30'],
            'orientation' => ['nullable', 'string'],
            'color' => ['nullable', 'string'],
        ]);

        $search = Unsplash::search($validated['query'])
            ->page((int) ($validated['page'] ?? 1))
            ->perPage((int) ($validated['per_page'] ?? 24));

        if (($orientation = Orientation::tryFrom((string) ($validated['orientation'] ?? ''))) !== null) {
            $search = $search->orientation($orientation);
        }

        if (($color = Color::tryFrom((string) ($validated['color'] ?? ''))) !== null) {
            $search = $search->color($color);
        }

        /** @var SearchResult $result */
        $result = $search->get();

        return new JsonResponse([
            'total' => $result->total,
            'total_pages' => $result->totalPages,
            'page' => $result->page,
            'results' => $result->photos->map(function (Photo $photo): array {
                $attribution = $photo->attribution();

                return [
                    'id' => $photo->id,
                    // Thumbnails keep the natural ratio so a grid can lay out
                    // tiles before the images arrive.
                    'thumb' => $photo->url(width: 400),
                    'width' => $photo->width,
                    'height' => $photo->height,
                    'aspect_ratio' => $photo->aspectRatio(),
                    'color' => $photo->color,
                    'blur_hash' => $photo->blurHash,
                    'alt' => $photo->alt(),
                    'description' => $photo->description,
                    // A picker displays photos, so it has to credit them too.
                    // The tagged URLs are supplied here so a client cannot
                    // reconstruct them incorrectly.
                    'author' => $photo->author->name,
                    'author_username' => $photo->author->username,
                    'author_url' => $attribution->authorUrl(),
                    'unsplash_url' => $attribution->unsplashUrl(),
                    'attribution_html' => $attribution->toHtml(),
                    'attribution_text' => $attribution->toText(),
                ];
            })->all(),
        ]);
    }

    /**
     * Add the chosen photos to a pool.
     */
    public function curate(Request $request, Config $config): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:30'],
            'ids.*' => ['required', 'string', 'max:32'],
            'pool' => ['nullable', 'string', 'max:255'],
        ]);

        /** @var array<int, string> $ids */
        $ids = $validated['ids'];

        $photos = Unsplash::photosByIds($ids);

        $assets = Unsplash::curateMany($photos, $validated['pool'] ?? $config->defaultPool());

        return new JsonResponse([
            'curated' => $assets->count(),
            'pool' => $validated['pool'] ?? $config->defaultPool(),
            // Both keys are returned: the Unsplash id identifies the photo, and
            // the primary key is what a form field attaches to a record.
            'assets' => $assets->map(fn (UnsplashAsset $asset): array => [
                'id' => $asset->getKey(),
                'unsplash_id' => $asset->unsplash_id,
                'thumb' => $asset->url(width: 400),
                'alt' => $asset->alt(),
                'color' => $asset->placeholderColor(),
            ])->values()->all(),
        ]);
    }
}
