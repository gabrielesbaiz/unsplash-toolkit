<?php

declare(strict_types=1);

namespace Gabrielesbaiz\UnsplashToolkit\Commands;

use Gabrielesbaiz\UnsplashToolkit\Enums\AssetStatus;
use Gabrielesbaiz\UnsplashToolkit\Models\UnsplashAsset;
use Gabrielesbaiz\UnsplashToolkit\Support\Compliance;
use Gabrielesbaiz\UnsplashToolkit\Support\Config;
use Gabrielesbaiz\UnsplashToolkit\Support\ImageUrl;
use Illuminate\Console\Command;
use Throwable;

/**
 * Audits the host application against the Unsplash API Guidelines.
 *
 * Exits non-zero on a violation so it can run in CI and fail a build, rather
 * than leaving compliance to be rediscovered during a review.
 *
 * @see https://help.unsplash.com/api-guidelines
 */
class DoctorCommand extends Command
{
    protected $signature = 'unsplash:doctor {--strict : Treat warnings as failures}';

    protected $description = 'Audit this application against the Unsplash API Guidelines';

    /** @var array<int, array{rule: string, status: string, detail: string}> */
    private array $results = [];

    private bool $failed = false;

    private bool $warned = false;

    /**
     * Execute the console command.
     */
    public function handle(Config $config, Compliance $compliance, ImageUrl $imageUrl): int
    {
        $this->components->info('Auditing the Unsplash API Guidelines.');

        $this->checkCredentials($config, $compliance);
        $this->checkAttribution($config, $compliance);
        $this->checkHotlinking($config, $compliance);
        $this->checkTrackingParameters($imageUrl);
        $this->checkDownloadEvents();
        $this->checkRateLimit($config);
        $this->checkPools($config);

        $this->table(['Rule', 'Status', 'Detail'], array_map(
            static fn (array $row): array => [$row['rule'], $row['status'], $row['detail']],
            $this->results,
        ));

        if ($this->failed) {
            $this->components->error('This application does not currently satisfy the Unsplash API Guidelines.');

            return self::FAILURE;
        }

        if ($this->warned && $this->option('strict')) {
            $this->components->error('Warnings were reported and --strict was given.');

            return self::FAILURE;
        }

        $this->components->info('No guideline violations found.');

        return self::SUCCESS;
    }

    /**
     * C7: credentials must remain confidential.
     */
    private function checkCredentials(Config $config, Compliance $compliance): void
    {
        if ($config->accessKey() === null) {
            $this->recordFail('C7 credentials', 'No access key is configured.');

            return;
        }

        $this->recordPass('C7 credentials', 'Access key '.$compliance->mask($config->accessKey()).' is set server side.');
    }

    /**
     * C5: every photo must credit the photographer and Unsplash.
     */
    private function checkAttribution(Config $config, Compliance $compliance): void
    {
        if (! $compliance->hasAttributionName()) {
            $this->recordFail(
                'C5 attribution',
                'compliance.app_name is unset or still the placeholder, so attribution links have no utm_source.',
            );

            return;
        }

        $this->recordPass('C5 attribution', 'utm_source is "'.$config->appName().'".');
    }

    /**
     * C1 and C2: hotlink by default, store only with written permission.
     */
    private function checkHotlinking(Config $config, Compliance $compliance): void
    {
        if (! $config->allowsLocalStorage()) {
            $this->recordPass('C1 hotlinking', 'Local storage is disabled, so images are served by Unsplash.');

            return;
        }

        if ($config->storagePermissionReference() === null) {
            $this->recordFail(
                'C1 hotlinking',
                'Local storage is enabled with no recorded permission from Unsplash.',
            );

            return;
        }

        $this->recordWarn(
            'C1 hotlinking',
            'Local storage is enabled under permission "'.$config->storagePermissionReference().'".',
        );

        if (! $this->hasDatabase()) {
            return;
        }

        $stored = UnsplashAsset::query()->whereNotNull('path')->count();

        if ($stored > 0) {
            $this->recordWarn('C1 hotlinking', "{$stored} photos are served from a local disk rather than Unsplash.");
        }
    }

    /**
     * C4: the ixid parameter must survive every URL manipulation.
     */
    private function checkTrackingParameters(ImageUrl $imageUrl): void
    {
        if (! $this->hasDatabase()) {
            $this->recordSkip('C4 tracking', 'No database connection is available.');

            return;
        }

        $missing = 0;
        $checked = 0;

        UnsplashAsset::query()->limit(200)->each(function (UnsplashAsset $asset) use ($imageUrl, &$missing, &$checked): void {
            $url = $asset->rawUrl();

            if ($url === '') {
                return;
            }

            $checked++;

            if (! $imageUrl->hasTrackingParameter($asset->url(width: 1280))) {
                $missing++;
            }
        });

        if ($checked === 0) {
            $this->recordSkip('C4 tracking', 'No curated photos have hotlink URLs yet.');

            return;
        }

        if ($missing > 0) {
            $this->recordFail('C4 tracking', "{$missing} of {$checked} photos produce URLs without an ixid.");

            return;
        }

        $this->recordPass('C4 tracking', "All {$checked} sampled photos keep their ixid when resized.");
    }

    /**
     * C3: choosing a photo must report a download event.
     */
    private function checkDownloadEvents(): void
    {
        if (! $this->hasDatabase()) {
            $this->recordSkip('C3 download events', 'No database connection is available.');

            return;
        }

        $total = UnsplashAsset::query()->count();

        if ($total === 0) {
            $this->recordSkip('C3 download events', 'No photos have been curated yet.');

            return;
        }

        $missing = UnsplashAsset::query()
            ->where(fn ($query) => $query->whereNull('download_location')->orWhere('download_location', ''))
            ->count();

        if ($missing > 0) {
            $this->recordWarn(
                'C3 download events',
                "{$missing} of {$total} photos have no download_location. Run \"php artisan unsplash:refresh --backfill\".",
            );

            return;
        }

        $this->recordPass('C3 download events', "All {$total} curated photos carry a download location.");
    }

    /**
     * C6: stay inside the granted rate limit.
     */
    private function checkRateLimit(Config $config): void
    {
        if (! $config->rateLimitEnabled()) {
            $this->recordWarn('C6 rate limit', 'Local rate limiting is disabled, so nothing guards the hourly budget.');

            return;
        }

        $this->recordPass('C6 rate limit', "Capped at {$config->rateLimitPerHour()} requests per hour.");
    }

    /**
     * Report pools that are about to run dry.
     */
    private function checkPools(Config $config): void
    {
        if (! $this->hasDatabase()) {
            $this->recordSkip('Pool health', 'No database connection is available.');

            return;
        }

        $minimum = $config->poolMinSize();

        /** @var array<string, int> $pools */
        $pools = UnsplashAsset::query()
            ->where('status', AssetStatus::Active->value)
            ->selectRaw('pool, count(*) as total')
            ->groupBy('pool')
            ->pluck('total', 'pool')
            ->all();

        if ($pools === []) {
            $this->recordWarn('Pool health', 'No active photos are curated in any pool.');

            return;
        }

        foreach ($pools as $pool => $total) {
            if ($total < $minimum) {
                $this->recordWarn('Pool health', "Pool [{$pool}] has {$total} active photos, below the minimum of {$minimum}.");
            }
        }

        $this->recordPass('Pool health', count($pools).' pools checked.');
    }

    /**
     * Determine if the package tables can be queried.
     */
    private function hasDatabase(): bool
    {
        try {
            UnsplashAsset::query()->limit(1)->exists();

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Record a passing check.
     */
    private function recordPass(string $rule, string $detail): void
    {
        $this->results[] = ['rule' => $rule, 'status' => 'OK', 'detail' => $detail];
    }

    /**
     * Record a failing check.
     */
    private function recordFail(string $rule, string $detail): void
    {
        $this->failed = true;

        $this->results[] = ['rule' => $rule, 'status' => 'FAIL', 'detail' => $detail];
    }

    /**
     * Record a warning.
     */
    private function recordWarn(string $rule, string $detail): void
    {
        $this->warned = true;

        $this->results[] = ['rule' => $rule, 'status' => 'WARN', 'detail' => $detail];
    }

    /**
     * Record a skipped check.
     */
    private function recordSkip(string $rule, string $detail): void
    {
        $this->results[] = ['rule' => $rule, 'status' => 'SKIP', 'detail' => $detail];
    }
}
