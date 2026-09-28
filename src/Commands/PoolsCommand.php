<?php

declare(strict_types=1);

namespace Gabrielesbaiz\UnsplashToolkit\Commands;

use Gabrielesbaiz\UnsplashToolkit\Enums\AssetStatus;
use Gabrielesbaiz\UnsplashToolkit\Models\UnsplashAsset;
use Gabrielesbaiz\UnsplashToolkit\Support\Config;
use Illuminate\Console\Command;

class PoolsCommand extends Command
{
    protected $signature = 'unsplash:pools';

    protected $description = 'Show the size and health of every curated pool';

    /**
     * Execute the console command.
     */
    public function handle(Config $config): int
    {
        $rows = UnsplashAsset::query()
            ->selectRaw('pool, status, count(*) as total')
            ->groupBy('pool', 'status')
            ->get();

        if ($rows->isEmpty()) {
            $this->components->warn('No photos have been curated yet.');

            return self::SUCCESS;
        }

        $minimum = $config->poolMinSize();
        $pools = [];

        foreach ($rows as $row) {
            /** @var string $pool */
            $pool = $row->getAttribute('pool');
            $status = $row->getAttribute('status');
            $status = $status instanceof AssetStatus ? $status->value : (string) $status;

            $pools[$pool][$status] = (int) $row->getAttribute('total');
        }

        $table = [];

        foreach ($pools as $pool => $counts) {
            $active = $counts[AssetStatus::Active->value] ?? 0;

            $table[] = [
                $pool,
                $active,
                $counts[AssetStatus::Unavailable->value] ?? 0,
                $counts[AssetStatus::Archived->value] ?? 0,
                $active < $minimum ? 'depleted' : 'healthy',
            ];
        }

        $this->table(['Pool', 'Active', 'Unavailable', 'Archived', 'Health'], $table);

        return self::SUCCESS;
    }
}
