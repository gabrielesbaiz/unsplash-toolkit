<?php

declare(strict_types=1);

namespace Gabrielesbaiz\UnsplashToolkit\Nova\Actions;

use Gabrielesbaiz\UnsplashToolkit\Facades\Unsplash;
use Gabrielesbaiz\UnsplashToolkit\Support\Config;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;
use Throwable;

/**
 * Curates explicitly chosen Unsplash photos from Nova.
 *
 * Photos are named by id, so the operator decides which ones are approved. There
 * is no "import N random photos" mode: that would be the automated bulk use the
 * API Guidelines ask applications not to make.
 *
 * Subclass this to set your own pool, fields or authorization.
 */
class CurateUnsplashPhotos extends Action
{
    use Queueable;

    public $name = 'Curate Unsplash photos';

    public $standalone = true;

    /**
     * The pool curated photos are added to.
     */
    protected ?string $pool = null;

    /**
     * Perform the action.
     *
     * @param  Collection<int, mixed>  $models
     * @return mixed an array on Nova 4, an ActionResponse on Nova 5
     */
    public function handle(ActionFields $fields, Collection $models): mixed
    {
        $ids = collect(explode(',', (string) $fields->get('ids')))
            ->map(fn (string $id): string => trim($id))
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return Action::danger(__('Enter at least one Unsplash photo id.'));
        }

        $pool = $this->pool ?? app(Config::class)->defaultPool();

        try {
            $photos = Unsplash::photosByIds($ids->all());

            if ($photos->isEmpty()) {
                return Action::danger(__('None of those photo ids could be found on Unsplash.'));
            }

            $assets = Unsplash::curateMany($photos, $pool);
        } catch (Throwable $exception) {
            return Action::danger($exception->getMessage());
        }

        return Action::message(__(':count photos curated into :pool.', [
            'count' => $assets->count(),
            'pool' => $pool,
        ]));
    }

    /**
     * Get the fields available on the action.
     *
     * @return array<int, mixed>
     */
    public function fields(NovaRequest $request): array
    {
        return [
            Text::make(__('Unsplash photo ids'), 'ids')
                ->rules('required')
                ->help(__('Comma separated. Find them with "php artisan unsplash:search" or the picker.')),
        ];
    }

    /**
     * Set the pool curated photos are added to.
     */
    public function pool(string $pool): static
    {
        $this->pool = $pool;

        return $this;
    }
}
