# Changelog

All notable changes to `unsplash-toolkit` will be documented in this file.

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
