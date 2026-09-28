<?php

declare(strict_types=1);

namespace Gabrielesbaiz\UnsplashToolkit\Commands;

use Gabrielesbaiz\UnsplashToolkit\Facades\Unsplash;
use Gabrielesbaiz\UnsplashToolkit\Support\Config;
use Gabrielesbaiz\UnsplashToolkit\Support\Throttle;
use Illuminate\Console\Command;
use Throwable;

class StatusCommand extends Command
{
    protected $signature = 'unsplash:status';

    protected $description = 'Show the Unsplash API rate limit and how much of it is spent';

    /**
     * Execute the console command.
     */
    public function handle(Config $config, Throttle $throttle): int
    {
        $this->components->info('Querying Unsplash.');

        try {
            Unsplash::stats();
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $reported = Unsplash::rateLimit();

        $rows = [
            ['Local budget', $config->rateLimitPerHour().' per hour'],
            ['Local spent', (string) $throttle->used()],
            ['Local remaining', (string) $throttle->remaining()],
        ];

        if ($reported !== null) {
            $rows[] = ['Unsplash limit', $reported->limit !== null ? (string) $reported->limit : 'unknown'];
            $rows[] = ['Unsplash remaining', $reported->remaining !== null ? (string) $reported->remaining : 'unknown'];
            $rows[] = ['Tier', $reported->isDemo() ? 'demo (50/hour)' : 'production'];
        }

        $this->table(['Metric', 'Value'], $rows);

        $this->components->info('Curated pools are read from your database, so rendering pages does not spend this budget.');

        return self::SUCCESS;
    }
}
