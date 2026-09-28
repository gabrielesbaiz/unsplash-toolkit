<?php

declare(strict_types=1);

namespace Gabrielesbaiz\UnsplashToolkit\Models;

use Gabrielesbaiz\UnsplashToolkit\Data\Attribution;
use Gabrielesbaiz\UnsplashToolkit\Data\Photo;
use Gabrielesbaiz\UnsplashToolkit\Database\Factories\UnsplashAssetFactory;
use Gabrielesbaiz\UnsplashToolkit\Enums\AssetStatus;
use Gabrielesbaiz\UnsplashToolkit\Enums\Size;
use Gabrielesbaiz\UnsplashToolkit\Events\PoolDepleted;
use Gabrielesbaiz\UnsplashToolkit\Exceptions\PoolDepletedException;
use Gabrielesbaiz\UnsplashToolkit\Support\Compliance;
use Gabrielesbaiz\UnsplashToolkit\Support\Config;
use Gabrielesbaiz\UnsplashToolkit\Support\ImageUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * A photo that has been approved for use.
 *
 * This is a registry entry, not a file. It records which photo was chosen and
 * the hotlinked URLs Unsplash returned for it, so the application keeps full
 * editorial control over which images appear while the bytes are still served
 * by Unsplash, as the API Guidelines require.
 *
 * @property int $id
 * @property string $unsplash_id
 * @property string $pool
 * @property AssetStatus $status
 * @property array<string, string> $urls
 * @property string|null $download_location
 * @property string|null $html_link
 * @property int $width
 * @property int $height
 * @property string|null $color
 * @property string|null $blurhash
 * @property string|null $description
 * @property string|null $alt_description
 * @property string $author_name
 * @property string|null $author_username
 * @property string $author_link
 * @property string|null $disk
 * @property string|null $path
 * @property string|null $mime
 * @property int|null $size
 * @property array<string, mixed>|null $meta
 * @property Carbon|null $curated_at
 * @property Carbon|null $last_verified_at
 *
 * @method static Builder<static> pool(string|null $pool = null)
 * @method static Builder<static> active()
 */
class UnsplashAsset extends Model
{
    /** @use HasFactory<UnsplashAssetFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * Create a new model instance.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $config = self::config();

        $this->setTable($config->assetsTable());
        $this->setConnection($config->connection());
    }

    /**
     * Get the attribute casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AssetStatus::class,
            'urls' => 'array',
            'meta' => 'array',
            'width' => 'integer',
            'height' => 'integer',
            'size' => 'integer',
            'curated_at' => 'datetime',
            'last_verified_at' => 'datetime',
        ];
    }

    /**
     * Bootstrap the model.
     */
    protected static function booted(): void
    {
        static::deleting(function (self $asset): void {
            // A registry entry usually owns no file. When the gated storage path
            // wrote one, delete() alone is idempotent, so there is no exists() probe.
            if ($asset->disk !== null && $asset->path !== null) {
                Storage::disk($asset->disk)->delete($asset->path);
            }

            // The pivot has a cascading foreign key on fresh installs, but an
            // installation upgraded from v1 has no constraint, so clear it here too.
            $asset->detachAll();
        });
    }

    /**
     * Restrict the query to a named pool.
     *
     * @param  Builder<static>  $query
     */
    public function scopePool(Builder $query, ?string $pool = null): void
    {
        $query->where('pool', $pool ?? self::config()->defaultPool());
    }

    /**
     * Restrict the query to photos that may be served.
     *
     * @param  Builder<static>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', AssetStatus::Active->value);
    }

    /**
     * Get every model of the given type this photo is attached to.
     *
     * @template TRelated of Model
     *
     * @param  class-string<TRelated>  $related
     * @return MorphToMany<TRelated, $this>
     */
    public function attachedTo(string $related): MorphToMany
    {
        return $this->morphedByMany($related, 'unsplashable', self::config()->pivotTable());
    }

    /**
     * Remove every attachment to this photo.
     *
     * The table name comes from config rather than being hardcoded into raw SQL,
     * so a prefixed or non-default connection is respected.
     */
    public function detachAll(): void
    {
        $this->getConnection()
            ->table(self::config()->pivotTable())
            ->where('unsplash_asset_id', $this->getKey())
            ->delete();
    }

    /**
     * Get the model that curated this photo.
     *
     * @return MorphTo<Model, $this>
     */
    public function curatedBy(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get one random active photo from a pool.
     *
     * This reads the application's own registry, so rendering a page costs no
     * Unsplash API requests at all.
     */
    public static function random(?string $pool = null): ?static
    {
        $pool ??= self::config()->defaultPool();

        /** @var static|null $asset */
        $asset = static::query()->pool($pool)->active()->inRandomOrder()->first();

        static::guardPoolDepth($pool);

        return $asset;
    }

    /**
     * Get one random active photo from a pool, failing when there is none.
     *
     * Use this where a missing image is a bug rather than something to degrade
     * around.
     */
    public static function randomOrFail(?string $pool = null): static
    {
        $pool ??= self::config()->defaultPool();

        return static::random($pool) ?? throw PoolDepletedException::make($pool);
    }

    /**
     * Get one random active photo from a pool, cached for repeated renders.
     */
    public static function cachedRandom(?string $pool = null, ?int $ttl = null): ?static
    {
        $config = self::config();
        $pool ??= $config->defaultPool();

        $cached = Cache::remember(
            "unsplash-toolkit:pool:{$pool}",
            $ttl ?? $config->selectionTtl(),
            fn (): ?int => static::random($pool)?->getKey(),
        );

        if ($cached === null) {
            return null;
        }

        /** @var static|null $asset */
        $asset = static::query()->whereKey($cached)->active()->first();

        // A photo that went unavailable while cached should not be served.
        if ($asset === null) {
            Cache::forget("unsplash-toolkit:pool:{$pool}");
        }

        return $asset;
    }

    /**
     * Get the hotlinked URL Unsplash returned for the given size, unmodified.
     */
    public function rawUrl(Size $size = Size::Regular): string
    {
        $urls = $this->urls ?? [];

        return $urls[$size->value] ?? $urls['regular'] ?? (reset($urls) ?: '');
    }

    /**
     * Get a hotlinked URL, optionally resized through Unsplash's image parameters.
     */
    public function url(
        Size $size = Size::Regular,
        ?int $width = null,
        ?int $height = null,
        ?int $quality = null,
        ?string $format = null,
        ?string $fit = null,
        ?int $dpr = null,
    ): string {
        $url = $this->rawUrl($size);

        if ($width === null && $height === null && $quality === null && $format === null && $fit === null && $dpr === null) {
            return $url;
        }

        return app(ImageUrl::class)->sized($url, $width, $height, $quality, $format, $fit, $dpr);
    }

    /**
     * Build a responsive srcset for this photo.
     *
     * @param  array<int, int>|null  $widths
     */
    public function srcset(?array $widths = null, bool $preserveAspectRatio = true, ?int $quality = null): string
    {
        $url = $this->rawUrl(Size::Raw) !== '' ? $this->rawUrl(Size::Raw) : $this->rawUrl();

        return app(ImageUrl::class)->srcset(
            $url,
            $widths,
            $preserveAspectRatio ? $this->aspectRatio() : null,
            $quality,
        );
    }

    /**
     * Get the aspect ratio, or null when the dimensions are unknown.
     */
    public function aspectRatio(): ?float
    {
        if ($this->width <= 0 || $this->height <= 0) {
            return null;
        }

        return $this->width / $this->height;
    }

    /**
     * Get the photographer credit for this photo.
     */
    public function attribution(): Attribution
    {
        return new Attribution(
            authorName: $this->author_name,
            authorLink: $this->author_link,
            appName: app(Compliance::class)->attributionName(),
        );
    }

    /**
     * Get the best available alternative text.
     */
    public function alt(): string
    {
        return $this->alt_description
            ?? $this->description
            ?? "Photo by {$this->author_name} on Unsplash";
    }

    /**
     * Get the colour rendered while the image loads.
     */
    public function placeholderColor(): string
    {
        return $this->color ?? self::config()->fallbackColor();
    }

    /**
     * Determine if this photo may be served.
     */
    public function isSelectable(): bool
    {
        return $this->status->isSelectable();
    }

    /**
     * Get the Unsplash page for this photo.
     */
    public function unsplashUrl(): string
    {
        return $this->html_link ?? "https://unsplash.com/photos/{$this->unsplash_id}";
    }

    /**
     * Build the attributes describing the given photo.
     *
     * @return array<string, mixed>
     */
    public static function attributesFrom(Photo $photo): array
    {
        return [
            'unsplash_id' => $photo->id,
            'urls' => $photo->urls,
            'download_location' => $photo->downloadLocation,
            'html_link' => $photo->htmlLink,
            'width' => $photo->width,
            'height' => $photo->height,
            'color' => $photo->color,
            'blurhash' => $photo->blurHash,
            'description' => $photo->description,
            'alt_description' => $photo->altDescription,
            'author_name' => $photo->author->name,
            'author_username' => $photo->author->username,
            'author_link' => $photo->author->link,
        ];
    }

    /**
     * Warn when a pool is running out of approved photos.
     */
    protected static function guardPoolDepth(string $pool): void
    {
        $config = self::config();

        if (! $config->eventsEnabled()) {
            return;
        }

        $minimum = $config->poolMinSize();

        if ($minimum <= 0) {
            return;
        }

        $remaining = static::query()->pool($pool)->active()->count();

        if ($remaining < $minimum) {
            PoolDepleted::dispatch($pool, $remaining, $minimum);
        }
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return UnsplashAssetFactory
     */
    protected static function newFactory(): Factory
    {
        return UnsplashAssetFactory::new();
    }

    /**
     * Resolve the package configuration.
     */
    protected static function config(): Config
    {
        return app(Config::class);
    }
}
