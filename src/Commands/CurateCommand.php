<?php

declare(strict_types=1);

namespace Gabrielesbaiz\UnsplashToolkit\Commands;

use Gabrielesbaiz\UnsplashToolkit\Facades\Unsplash;
use Gabrielesbaiz\UnsplashToolkit\Support\Config;
use Illuminate\Console\Command;
use Throwable;

class CurateCommand extends Command
{
    protected $signature = 'unsplash:curate
                            {id* : One or more Unsplash photo ids}
                            {--pool= : The pool to add them to}';

    protected $description = 'Approve Unsplash photos for use and add them to a pool';

    /**
     * Execute the console command.
     */
    public function handle(Config $config): int
    {
        /** @var array<int, string> $ids */
        $ids = $this->argument('id');

        $pool = is_string($this->option('pool')) && $this->option('pool') !== ''
            ? (string) $this->option('pool')
            : $config->defaultPool();

        $failed = 0;

        foreach ($ids as $id) {
            try {
                $asset = Unsplash::curate(Unsplash::photo($id), $pool);

                $this->components->info("Curated [{$asset->unsplash_id}] by {$asset->author_name} into [{$pool}].");
            } catch (Throwable $exception) {
                $failed++;

                $this->components->error("Could not curate [{$id}]: {$exception->getMessage()}");
            }
        }

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
