<?php

declare(strict_types=1);

namespace Gabrielesbaiz\UnsplashToolkit\Commands;

use Gabrielesbaiz\UnsplashToolkit\Data\SearchResult;
use Gabrielesbaiz\UnsplashToolkit\Facades\Unsplash;
use Illuminate\Console\Command;

class SearchCommand extends Command
{
    protected $signature = 'unsplash:search
                            {query : The search term}
                            {--per-page=10 : How many results to show}
                            {--orientation= : landscape, portrait or squarish}
                            {--color= : Filter results by colour}';

    protected $description = 'Search Unsplash photos';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $request = Unsplash::search((string) $this->argument('query'))
            ->perPage((int) $this->option('per-page'));

        if (is_string($orientation = $this->option('orientation')) && $orientation !== '') {
            $request = $request->orientation($orientation);
        }

        if (is_string($color = $this->option('color')) && $color !== '') {
            $request = $request->color($color);
        }

        /** @var SearchResult $result */
        $result = $request->get();

        if ($result->photos->isEmpty()) {
            $this->components->warn('No photos matched that search.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Photographer', 'Size', 'Description'],
            $result->photos->map(fn ($photo): array => [
                $photo->id,
                $photo->author->name,
                "{$photo->width}x{$photo->height}",
                mb_strimwidth((string) ($photo->description ?? $photo->altDescription ?? ''), 0, 48, '...'),
            ])->all(),
        );

        $this->components->info("{$result->total} results. Curate one with: php artisan unsplash:curate <id>");

        return self::SUCCESS;
    }
}
