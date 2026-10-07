<?php

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Rishadblack\WireTomselect\WireTomselectServiceProvider;

it('registers the package views', function () {
    expect(view()->exists('wire-tomselect::search'))->toBeTrue();
});

it('merges the package config with defaults', function () {
    expect(config('wire-tomselect.max_options'))->toBe(20)
        ->and(config('wire-tomselect.max_options_limit'))->toBe(100)
        ->and(config('wire-tomselect.load_throttle'))->toBe(300)
        ->and(config('wire-tomselect.min_search_length'))->toBe(1);
});

it('publishes the config and views under their own tags', function (string $tag, string $target) {
    $paths = ServiceProvider::pathsToPublish(WireTomselectServiceProvider::class, $tag);

    expect($paths)->toHaveCount(1)
        ->and(Str::of(array_values($paths)[0])->replace(DIRECTORY_SEPARATOR, '/')->toString())->toEndWith($target);
})->with([
    'config' => ['wire-tomselect-config', 'config/wire-tomselect.php'],
    'views' => ['wire-tomselect-views', 'views/vendor/wire-tomselect'],
]);
