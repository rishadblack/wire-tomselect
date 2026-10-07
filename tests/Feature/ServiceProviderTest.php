<?php

use Rishadblack\WireTomselect\WireTomselect;

it('registers the wire-tomselect singleton', function () {
    expect(app('wire-tomselect'))->toBeInstanceOf(WireTomselect::class)
        ->and(app('wire-tomselect'))->toBe(app('wire-tomselect'));
});

it('registers the package views', function () {
    expect(view()->exists('wire-tomselect::search'))->toBeTrue();
});

it('merges the package config', function () {
    expect(config('wire-tomselect'))->toBeArray();
});
