<?php

declare(strict_types=1);

use Gabrielesbaiz\UnsplashToolkit\Exceptions\UnsplashException;
use Gabrielesbaiz\UnsplashToolkit\Facades\Unsplash;
use Gabrielesbaiz\UnsplashToolkit\Support\Compliance;
use Illuminate\Support\Facades\Http;

/**
 * C7: "Your application's Access Key and Secret Key must remain confidential."
 */
it('sends the access key as a header, never in a query string', function (): void {
    Http::fake(['api.unsplash.com/*' => Http::response(['photos' => 1], 200)]);

    Unsplash::stats();

    Http::assertSent(function ($request): bool {
        return $request->hasHeader('Authorization', 'Client-ID test-access-key')
            && ! str_contains($request->url(), 'test-access-key');
    });
});

it('redacts credentials from error messages', function (): void {
    config()->set('unsplash-toolkit.secret_key', 'super-secret');

    Http::fake([
        'api.unsplash.com/*' => Http::response('failed for key test-access-key and super-secret', 500),
    ]);

    try {
        Unsplash::stats();

        $this->fail('The request did not fail.');
    } catch (UnsplashException $exception) {
        expect($exception->getMessage())
            ->not->toContain('test-access-key')
            ->not->toContain('super-secret')
            ->toContain('[redacted]');
    }
});

it('masks a key when it is displayed', function (): void {
    expect(app(Compliance::class)->mask('abcdefghijklmnop'))
        ->toBe('abcd********op')
        ->not->toContain('efghijklmn');
});

it('never renders the key into a Blade view', function (): void {
    $views = glob(__DIR__.'/../../resources/views/components/*.blade.php') ?: [];

    expect($views)->not->toBeEmpty();

    foreach ($views as $view) {
        $contents = (string) file_get_contents($view);

        expect($contents)
            ->not->toContain('access_key')
            ->not->toContain('secret_key');
    }
});
