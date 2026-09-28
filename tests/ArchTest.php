<?php

declare(strict_types=1);

arch('it will not use debugging functions')
    ->expect(['dd', 'dump', 'ray', 'var_dump'])
    ->each->not->toBeUsed();

arch('it does not download images with file_get_contents')
    ->expect('file_get_contents')
    ->not->toBeUsed();

arch('it does not talk to Guzzle directly')
    ->expect('GuzzleHttp')
    ->not->toBeUsedIn('Gabrielesbaiz\UnsplashToolkit');

arch('data objects are immutable')
    ->expect('Gabrielesbaiz\UnsplashToolkit\Data')
    ->toBeReadonly()
    ->ignoring('Gabrielesbaiz\UnsplashToolkit\Data\PhotoCollection');

arch('enums are backed by strings')
    ->expect('Gabrielesbaiz\UnsplashToolkit\Enums')
    ->toBeStringBackedEnums();

arch('every class declares strict types')
    ->expect('Gabrielesbaiz\UnsplashToolkit')
    ->toUseStrictTypes();

arch('exceptions extend the package exception')
    ->expect('Gabrielesbaiz\UnsplashToolkit\Exceptions')
    ->toExtend('Gabrielesbaiz\UnsplashToolkit\Exceptions\UnsplashException')
    ->ignoring('Gabrielesbaiz\UnsplashToolkit\Exceptions\UnsplashException');
