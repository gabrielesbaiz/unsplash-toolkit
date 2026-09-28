<?php

declare(strict_types=1);

namespace Gabrielesbaiz\UnsplashToolkit\Commands;

use Gabrielesbaiz\UnsplashToolkit\Enums\AssetStatus;
use Gabrielesbaiz\UnsplashToolkit\Models\UnsplashAsset;
use Gabrielesbaiz\UnsplashToolkit\Support\Curator;
use Illuminate\Console\Command;

/**
 * Checks that every curated photo still resolves on Unsplash.
 *
 * Because the package hotlinks rather than keeping copies, a photo the
 * photographer removes would otherwise render as a broken image. Marking it
 * unavailable takes it out of pool selection instead.
 */
class VerifyCommand extends Command
{
    protected $signature = 'unsplash:verify
                            {--pool= : Only verify one pool}
                            {--stale=7 : Only verify photos not checked for this many days}';

    protected $description = 'Verify that curated photos are still available on Unsplash';

    /**
     * Execute the console command.
     */
    public function handle(Curator $curator): int
    {
        $query = UnsplashAsset::query()->where('status', '!=', AssetStatus::Archived->value);

        if (is_string($pool = $this->option('pool')) && $pool !== '') {
            $query->pool($pool);
        }

        $stale = (int) $this->option('stale');

        if ($stale > 0) {
            $query->where(function ($query) use ($stale): void {
                $query->whereNull('last_verified_at')
                    ->orWhere('last_verified_at', '<=', now()->subDays($stale));
            });
        }

        $total = $query->count();

        if ($total === 0) {
            $this->components->info('Every curated photo has been verified recently.');

            return self::SUCCESS;
        }

        $unavailable = 0;
        $restored = 0;

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->each(function (UnsplashAsset $asset) use ($curator, $bar, &$unavailable, &$restored): void {
            $was = $asset->status;

            $curator->refresh($asset);

            if ($asset->status === AssetStatus::Unavailable && $was !== AssetStatus::Unavailable) {
                $unavailable++;
            }

            if ($asset->status === AssetStatus::Active && $was === AssetStatus::Unavailable) {
                $restored++;
            }

            $bar->advance();
        });

        $bar->finish();
        $this->newLine(2);

        $this->components->info("Verified {$total} photos: {$unavailable} newly unavailable, {$restored} restored.");

        return self::SUCCESS;
    }
}
