<?php

declare(strict_types=1);

namespace Gabrielesbaiz\UnsplashToolkit\Commands;

use Gabrielesbaiz\UnsplashToolkit\Models\UnsplashAsset;
use Gabrielesbaiz\UnsplashToolkit\Support\Curator;
use Illuminate\Console\Command;
use Throwable;

/**
 * Re-fetches curated photo metadata.
 *
 * Attribution has to name the photographer correctly, so a rename upstream must
 * be picked up. The --backfill mode rebuilds rows that only hold an Unsplash id,
 * which is how a v1 installation becomes a v2 registry without losing curation.
 */
class RefreshCommand extends Command
{
    protected $signature = 'unsplash:refresh
                            {--pool= : Only refresh one pool}
                            {--backfill : Only refresh rows that have no hotlink URLs yet}
                            {--assign-pool= : Move the refreshed rows into this pool}';

    protected $description = 'Refresh curated photo metadata from Unsplash';

    /**
     * Execute the console command.
     */
    public function handle(Curator $curator): int
    {
        $query = UnsplashAsset::query();

        if (is_string($pool = $this->option('pool')) && $pool !== '') {
            $query->pool($pool);
        }

        if ($this->option('backfill')) {
            $query->where(function ($query): void {
                $query->whereNull('urls')->orWhere('urls', '')->orWhere('urls', '[]');
            });
        }

        $assign = is_string($this->option('assign-pool')) && $this->option('assign-pool') !== ''
            ? (string) $this->option('assign-pool')
            : null;

        $total = $query->count();

        if ($total === 0) {
            $this->components->info('There is nothing to refresh.');

            return self::SUCCESS;
        }

        $this->components->info("Refreshing {$total} photos from Unsplash.");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $failed = 0;

        $query->each(function (UnsplashAsset $asset) use ($curator, $assign, $bar, &$failed): void {
            try {
                $curator->backfill($asset, $assign);
            } catch (Throwable $exception) {
                $failed++;

                $this->newLine();
                $this->components->warn("[{$asset->unsplash_id}] {$exception->getMessage()}");
            }

            $bar->advance();
        });

        $bar->finish();
        $this->newLine(2);

        if ($failed > 0) {
            $this->components->warn("{$failed} photos could not be refreshed.");
        }

        $this->components->info('Refresh complete.');

        return self::SUCCESS;
    }
}
