<?php

declare(strict_types=1);

namespace Gabrielesbaiz\UnsplashToolkit\Commands;

use Gabrielesbaiz\UnsplashToolkit\Support\ResponseCache;
use Illuminate\Console\Command;

class CacheClearCommand extends Command
{
    protected $signature = 'unsplash:cache-clear';

    protected $description = 'Flush cached Unsplash API responses';

    /**
     * Execute the console command.
     */
    public function handle(ResponseCache $cache): int
    {
        if ($cache->flush()) {
            $this->components->info('The Unsplash response cache has been flushed.');

            return self::SUCCESS;
        }

        $this->components->warn('The cache store could not be flushed.');

        return self::FAILURE;
    }
}
