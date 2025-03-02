# UnsplashToolkit

[![Latest Version on Packagist](https://img.shields.io/packagist/v/gabrielesbaiz/unsplash-toolkit.svg?style=flat-square)](https://packagist.org/packages/gabrielesbaiz/unsplash-toolkit)
[![Total Downloads](https://img.shields.io/packagist/dt/gabrielesbaiz/unsplash-toolkit.svg?style=flat-square)](https://packagist.org/packages/gabrielesbaiz/unsplash-toolkit)

A lightweight helper package to handle Unsplash photos

Original code from [marksitko/laravel-unsplash]([marksitko/laravel-unsplash](https://github.com/marksitko/laravel-unsplash)

## Features

- ✅ Fluent api to use the Unsplash within Laravel applications
- ✅ Works seamlessly with Laravel facades

## Installation

You can install the package via composer:

```bash
composer require gabrielesbaiz/unsplash-toolkit
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="unsplash-toolkit-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="unsplash-toolkit-config"
```

This is the contents of the published config file:

```php
return [
    'access_key' => env('UNSPLASH_ACCESS_KEY'),
    'store_in_database' => env('UNSPLASH_STORE_IN_DATABASE', false),
    'disk' => env('UNSPLASH_STORAGE_DISK', 'local'),
    'app_name' => env('UNSPLASH_APP_NAME', 'your_app_name'),
];
```

## Usage

### Basic Usage

Take a look at the full Unsplash API documentation https://unsplash.com/documentation

**Random Photos**
``` php 
// Returns the http response body.
$twoRandomPhotosOfSomePeoples = UnsplashToolkit::randomPhoto()
    ->orientation('portrait')
    ->term('people')
    ->count(2)
    ->toJson();

// Store the image in on your provided disc
$theNameFromTheStoredPhoto = UnsplashToolkit::randomPhoto()
    ->orientation('landscape')
    ->term('music')
    ->randomPhoto()
    ->store();
];
```

**Photos**
``` php 
$photos = UnsplashToolkit::photos()->toJson();
$photo = UnsplashToolkit::photo($id)->toJson();
$photosStatistics = UnsplashToolkit::photosStatistics($id)->toJson();
$trackPhotoDownload = UnsplashToolkit::trackPhotoDownload($id)->toJson();
```

**Users**
``` php 
$user = UnsplashToolkit::user($username)->toJson();
$userPortfolio = UnsplashToolkit::userPortfolio($username)->toJson();
$userPhotos = UnsplashToolkit::userPhotos($username)->toJson();
$userLikes = UnsplashToolkit::userLikes($username)->toJson();
$userCollections = UnsplashToolkit::userCollections($username)->toJson();
$userStatistics = UnsplashToolkit::userStatistics($username)->toJson();
```

**Search**
``` php 
$search = UnsplashToolkit::search()
    ->term('buildings')
    ->color('black_and_white')
    ->orientation('squarish')
    ->toJson();

$searchCollections = UnsplashToolkit::searchCollections()
    ->query('events')
    ->page($pageNumber)
    ->toJson();

$searchUsers = UnsplashToolkit::searchUsers()
    ->query('search_term')
    ->toJson();
```

**Collections**
``` php 
$collectionsList = UnsplashToolkit::collectionsList()
    ->page($pageNumber)
    ->perPage($itemsPerPage)
    ->toJson();

$featuredCollection = UnsplashToolkit::featuredCollection()
    ->page($pageNumber)
    ->perPage($itemsPerPage)
    ->toJson();

$showCollection = UnsplashToolkit::showCollection()
    ->id($collectionId)
    ->toJson();

$showCollectionPhotos = UnsplashToolkit::showCollectionPhotos()
    ->id($collectionId)
    ->toJson();

$showCollectionRelatedCollections = UnsplashToolkit::showCollectionRelatedCollections()
    ->id($collectionId)
    ->toJson();
```

**Stats**
``` php 
$totalStats = UnsplashToolkit::totalStats()->toJson();
$monthlyStats = UnsplashToolkit::monthlyStats()->toJson();
```

### Usage with Database

If you wanna persist automaticly some informations (i.e. Copyrights) about you stored images you have to run the published migrations.

Set the `.env` variable
```
UNSPLASH_STORE_IN_DATABASE=true
```

When you execute `store()`, the image is stored in your provided disc and informations like 
- the unsplash photo id
- the stored image name
- author name
- author link

Note that these informations are all required to use a Unsplash photo on your website.

### Trait

You can also use the `HasUnsplashables` Trait on any model.

**Example with HasUnsplashables Trait on the User Model**
``` php 
use Illuminate\Foundation\Auth\User as Authenticatable;

use Gabrielesbaiz\UnsplashToolkit\Traits\HasUnsplashables;

class User extends Authenticatable
{
    use HasUnsplashables;

    // ...
}
```

Now you are able to use it like:
``` php 
// store the unsplash asset in a morphToMany relation
$unsplashAsset = UnsplashToolkit::randomPhoto()->store();

User::unsplash()->save($unsplashAsset);

// retrive all related unsplash assets
User::find($userId)->unsplash();
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Mark Sitko](https://github.com/marksitko)
- [Gabriele Sbaiz](https://github.com/gabrielesbaiz)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
