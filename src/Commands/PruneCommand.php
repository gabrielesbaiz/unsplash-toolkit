<?php

declare(strict_types=1);

namespace Gabrielesbaiz\UnsplashToolkit\Commands;

use Gabrielesbaiz\UnsplashToolkit\Enums\AssetStatus;
use Gabrielesbaiz\UnsplashToolkit\Models\UnsplashAsset;
use Illuminate\Console\Command;

class PruneCommand extends Command
{
    protected $signature = 'unsplash:prune
                            {--pool= : Only prune one pool}
                            {--days=90 : Remove photos unavailable for this many days}';

    protected $description = 'Remove curated photos Unsplash no longer serves';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $query = UnsplashAsset::query()
            ->where('status', AssetStatus::Unavailable->value)
            ->where('last_verified_at', '<=', now()->subDays((int) $this->option('days')));

        if (is_string($pool = $this->option('pool')) && $pool !== '') {
            $query->pool($pool);
        }

        $total = $query->count();

        if ($total === 0) {
            $this->components->info('There is nothing to prune.');

            return self::SUCCESS;
        }

        if (! $this->confirm("Delete {$total} unavailable photos?", true)) {
            return self::SUCCESS;
        }

        // Deleted one at a time so the model hook clears attachments and any
        // file written under the gated storage path.
        $query->each(fn (UnsplashAsset $asset) => $asset->delete());

        $this->components->info("Pruned {$total} photos.");

        return self::SUCCESS;
    }
}
