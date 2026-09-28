# Upgrading from 1.x to 2.x

Version 2 is a rewrite. The public API changed, and so did what the package
stores: v1 downloaded image files, v2 records which photos you approved and
hotlinks them from Unsplash.

## Why the API changed

Three defects in 1.x could not be fixed without breaking the surface:

- Every config read used `config('unsplash.*')` while the published file was
  `unsplash-toolkit.php`, so the access key was always `null` and the package
  threw on construction. Config now goes through a typed `Support\Config` class
  with a single `KEY` constant, so the two can never drift apart again.
- The facade cached one instance whose `$query` was never reset, so parameters
  leaked between calls: `search()->term('cats')` followed by `photos()` sent
  `photos?query=cats`. The builder is now immutable.
- `toJson(): array` returned a `stdClass`, and `store(): string` returned a
  model. Both raised a `TypeError` on the documented happy path.

## Why storage changed

The [Unsplash API Guidelines](https://help.unsplash.com/api-guidelines) state:

> All API uses must use the hotlinked image URLs returned by the API under the
> `photo.urls` properties.

`store()` copied the bytes to your disk and served them from there, which is the
opposite. v2 hotlinks by default and keeps a **curated registry** instead: you
still decide exactly which photos appear, but Unsplash serves them.

You lose nothing you actually needed, and you gain working view tracking for the
photographer, responsive sizes, and no storage or egress bill.

## Step by step

### 1. Update the config

Delete any hand-made `config/unsplash.php` you created to work around the key
mismatch, then publish the real file:

```bash
php artisan vendor:publish --tag=unsplash-toolkit-config --force
```

Environment variables:

| 1.x | 2.x |
| --- | --- |
| `UNSPLASH_ACCESS_KEY` | unchanged |
| `UNSPLASH_APP_NAME` | unchanged, but now required and actually read |
| `UNSPLASH_STORE_IN_DATABASE` | removed: photos are always recorded |
| `UNSPLASH_STORAGE_DISK` | only used on the gated storage path |

`UNSPLASH_APP_NAME` becomes the `utm_source` of every credit. Leaving it unset,
or at the old `your_app_name` placeholder, now throws rather than emitting an
attribution that does not qualify.

### 2. Run the migrations

```bash
php artisan vendor:publish --tag=unsplash-toolkit-migrations
php artisan migrate
```

`upgrade_unsplash_tables_to_v2` is additive. It adds the registry columns, gives
`unsplashables` the primary key and indexes it shipped without, and renames the
non-idiomatic `unsplashables_id` / `unsplashables_type` columns to
`unsplashable_id` / `unsplashable_type`.

### 3. Backfill the registry

Existing rows already hold `unsplash_id`, which is all that is needed to rebuild
them. Nothing you curated is lost:

```bash
php artisan unsplash:refresh --backfill --assign-pool=default
```

### 4. Update your calls

| 1.x | 2.x |
| --- | --- |
| `UnsplashToolkit::search()->term('x')->toJson()` | `Unsplash::search('x')->get()` |
| `UnsplashToolkit::photo($id)->toJson()` | `Unsplash::photo($id)` |
| `UnsplashToolkit::randomPhoto()->term('x')->toJson()` | `Unsplash::random()->term('x')->get()` |
| `UnsplashToolkit::photos()->toJson()` | `Unsplash::photos()->get()` |
| `UnsplashToolkit::collectionsList()->toJson()` | `Unsplash::collections()->get()` |
| `UnsplashToolkit::showCollection($id)->toJson()` | `Unsplash::collection($id)` |
| `UnsplashToolkit::showCollectionPhotos($id)->toJson()` | `Unsplash::collectionPhotos($id)->get()` |
| `UnsplashToolkit::totalStats()->toJson()` | `Unsplash::stats()` |
| `UnsplashToolkit::trackPhotoDownload($id)` | automatic on `Unsplash::curate()` |
| `$result['urls']['regular']` | `$photo->url(Size::Regular)` |
| `$result['user']['name']` | `$photo->author->name` |
| `->store(null, 'regular')` | `Unsplash::curate($photo, pool: '...')` |
| `$asset->getFullCopyrightLink()` | `<x-unsplash::attribution :asset="$asset" />` |
| `use ...\Traits\HasUnsplashables` | `use ...\Concerns\HasUnsplashables` |
| `Facades\UnsplashToolkit` | `Facades\Unsplash` |

`->toArray()` is still available on a pending request when you want the raw
payload.

### 5. Serve hotlinked images

Anywhere you built a URL from a stored file, use the photo instead:

```diff
-return redirect()->away(Storage::disk('wallpapers')->url($wallpaper->name));
+return redirect()->away($wallpaper->url(width: 1920, quality: 80));
```

```diff
-{!! $wallpaper->getFullCopyrightLink() !!}
+<x-unsplash::attribution :asset="$wallpaper" />
```

The component renders nothing when the photo is null, so a page no longer fails
when a pool is empty.

### 6. Select from a pool

```diff
-$wallpaper = Cache::remember('wallpaper', 10, fn () => UnsplashAsset::inRandomOrder()->first());
+$wallpaper = UnsplashAsset::cachedRandom('login-backgrounds');
```

### 7. Schedule the health checks

Because images are hotlinked, a photo the photographer removes has to be taken
out of rotation:

```php
Schedule::command('unsplash:verify')->daily();
Schedule::command('unsplash:refresh')->weekly();
```

### 8. Check your work

```bash
php artisan unsplash:doctor
```

It audits this application against the guidelines and exits non-zero on a
violation, so it can run in CI.

## If you really must store image files

Only with written permission from Unsplash. Then:

```php
'compliance' => [
    'allow_local_storage' => true,
    'storage_permission_reference' => 'UNSPLASH-1234',
],
```

Both keys are required. `Unsplash::import()` throws
`HotlinkingRequiredException` otherwise.
