<?php

declare(strict_types=1);

use Gabrielesbaiz\UnsplashToolkit\Support\Curator;
use Gabrielesbaiz\UnsplashToolkit\UnsplashToolkit;

/**
 * C8: "The API is to be used for non-automated, high-quality, and authentic
 * experiences."
 *
 * The v1 API made "fetch N random photos and save them all" the documented
 * idiom. v2 removes that shape: curation takes photos a person chose.
 */
it('offers no entry point that fetches and saves arbitrary photos in bulk', function (): void {
    $forbidden = ['store', 'storeMany', 'importRandom', 'importMany', 'downloadRandom'];

    $methods = array_map(
        fn (ReflectionMethod $method): string => $method->getName(),
        (new ReflectionClass(UnsplashToolkit::class))->getMethods(ReflectionMethod::IS_PUBLIC),
    );

    expect(array_intersect($forbidden, $methods))->toBeEmpty();
});

it('only curates photos that were explicitly selected', function (): void {
    $curate = new ReflectionMethod(Curator::class, 'curateMany');

    $first = $curate->getParameters()[0];

    // An iterable of Photo objects, never a count and a search term.
    expect($first->getName())->toBe('photos')
        ->and((string) $first->getType())->toBe('iterable');
});

it('does not let a caller ask for a number of photos to import', function (): void {
    $import = new ReflectionMethod(UnsplashToolkit::class, 'import');

    $types = array_map(
        fn (ReflectionParameter $parameter): string => (string) $parameter->getType(),
        $import->getParameters(),
    );

    expect($types[0])->toBe('Gabrielesbaiz\UnsplashToolkit\Data\Photo');
});
