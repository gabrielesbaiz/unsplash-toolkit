# Changelog

All notable changes to `unsplash-toolkit` will be documented in this file.

## 2.0.4 - 2026-09-28

### Fixed

- `Throttle::used()` and `Throttle::availableIn()` are typed `int`, but
  `RateLimiter` hands back whatever the cache store holds and Redis holds strings.
  An application on the redis driver hit a `TypeError` where one on the array
  driver saw an int. The same value also reached `RateLimitExceededException`,
  whose `retryAfter` is typed `int`, so the throttle raised a `TypeError` instead
  of the rate limit exception exactly when the budget ran out.

## 2.0.3 - 2026-09-28

### Fixed

Four defects in the 1.x upgrade migration, all found by running it against a real
1.x installation. The path is now covered by `tests/Feature/UpgradeMigrationTest.php`.

- v1 stored the photographer in `author`; v2 reads `author_name`. The column was
  never mapped, so upgraded rows credited nobody and `attribution()` fataled.
- v1's `name` and `author` columns are `NOT NULL` and v2 writes neither, so every
  insert after the upgrade failed. They are now made nullable.
- The pivot's primary key was detected by looking for a column called `id`. An
  installation that had added its own corrective key under another name got a
  second auto-increment, which the database rejects. Any primary key now counts.
- SQLite cannot add a primary key to an existing table, so the pivot is rebuilt
  and its rows copied there instead.
- `attribution()` no longer fatals on a row that has not been backfilled yet.

## 2.0.2 - 2026-09-28

### Fixed

- Declared the HTTP dependencies the package actually uses. It builds on
  `Illuminate\Http\Client` but required only `illuminate/contracts`, so on a
  lowest-version resolution `guzzlehttp/promises` 1.x was installed, whose untyped
  `wait()` is incompatible with Laravel 12's `LazyPromise` and fataled on any
  `Http::pool()` call. `guzzlehttp/guzzle`, `illuminate/http` and `illuminate/support`
  are now required explicitly.

## 2.0.1 - 2026-09-28

### Fixed

- Declared Laravel 11 as the minimum. `UnsplashAsset` defines its casts through the
  `casts()` method, which Laravel 10 does not call, so on Laravel 10 the enum, array
  and date casts were silently ignored. Laravel 10 reached end of life in February 2026.
- Annotated the Blade components' view names as `view-string`, so PHPStan passes on a
  clean dependency resolution.
- Removed the Laravel 10 jobs from the test matrix: `pestphp/pest-plugin-laravel ^3`
  requires Laravel 11, so those jobs could never resolve.

## 2.0.0 - 2026-09-28

A rewrite. See [UPGRADE.md](UPGRADE.md) for the migration path.

### Fixed

- The package could not run at all: every config read used `config('unsplash.*')`
  while the published file was `unsplash-toolkit.php`, so the access key was
  always `null` and the constructor threw on every instantiation.
- `toJson(): array` returned a `stdClass` because `json_decode` was called
  without `true`, raising a `TypeError` on the documented happy path.
- `store(): string` returned an `UnsplashAsset` when `store_in_database` was on.
- Query parameters leaked between calls, because the facade cached one instance
  whose `$query` was never reset.
- `HasUnsplashables::boot()` overrode the host model's own `boot()`; it is now
  `bootHasUnsplashables()`.
- `getFullCopyrightLink()` interpolated third-party author names and links into
  HTML unescaped.
- `UnsplashAsset::assets(Model $model)` was a relation method taking an argument,
  so it could not be eager-loaded.
- The `unsplashables` table shipped with no primary key and no indexes.
- `unsplash_id` was typed `uuid`, which fails outright on PostgreSQL; Unsplash
  ids are short base62 strings.
- The `php` constraint `^7.3,^8.0|^8.1|...` was an empty set.

### Added

- Curated pools: approve photos into named sets and select from your own
  database, with no API calls at render time.
- Readonly DTOs (`Photo`, `Author`, `SearchResult`, `Attribution`, `RateLimit`)
  and enums (`Size`, `Orientation`, `Color`, `OrderBy`, `AssetStatus`).
- Response caching, retries with exponential backoff, timeouts, rate-limit
  handling and concurrent batch fetches.
- `<x-unsplash::image>` and `<x-unsplash::attribution>` Blade components.
- A picker proxy route that keeps the access key server side, returning layout
  metadata (aspect ratio, dominant colour, blurhash) and pre-tagged attribution
  so a client-side field can credit photographers correctly, plus the primary
  keys a form field attaches to a record.
- Commands: `search`, `curate`, `pools`, `verify`, `refresh`, `status`,
  `doctor`, `cache-clear`, `prune`.
- `Unsplash::fake()` and a compliance test suite covering each guideline.

### Changed

- Images are hotlinked by default, as the Unsplash API Guidelines require.
  Downloading bytes to a local disk is now gated behind explicit permission.
- Download events use `photo.links.download_location` rather than a hand-built
  URL, so the `ixid` is no longer dropped.
- Attribution requires an application name; a missing or placeholder value throws
  rather than emitting a credit that does not qualify.
- The facade is now `Facades\Unsplash`, and the trait moved to
  `Concerns\HasUnsplashables`.
- Laravel's HTTP client replaces raw Guzzle, so `Http::fake()` works in tests.

## 1.0.0 - 2025-03-04

- Initial release.
