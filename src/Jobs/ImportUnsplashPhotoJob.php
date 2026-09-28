<?php

declare(strict_types=1);

namespace Gabrielesbaiz\UnsplashToolkit\Jobs;

use Gabrielesbaiz\UnsplashToolkit\Enums\Size;
use Gabrielesbaiz\UnsplashToolkit\Facades\Unsplash;
use Gabrielesbaiz\UnsplashToolkit\Support\Config;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Curates a photo in the background.
 *
 * Curation is cheap, but doing it for a batch of selected photos inside a web
 * request is not: each one reports a download event to Unsplash.
 */
class ImportUnsplashPhotoJob implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly string $photoId,
        public readonly ?string $pool = null,
        public readonly bool $download = false,
        public readonly Size $size = Size::Regular,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(Config $config): void
    {
        $this->onConnection($this->connection ?? $config->queueConnection());
        $this->onQueue($this->queue ?? $config->queue());

        $photo = Unsplash::photo($this->photoId);

        if ($this->download) {
            Unsplash::import($photo, $this->size, $this->pool);

            return;
        }

        Unsplash::curate($photo, $this->pool);
    }
}
