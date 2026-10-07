<?php

namespace Rishadblack\WireTomselect;

use Illuminate\Support\ServiceProvider;

class WireTomselectServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/wire-tomselect.php', 'wire-tomselect');
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'wire-tomselect');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/wire-tomselect.php' => config_path('wire-tomselect.php'),
            ], 'wire-tomselect-config');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/wire-tomselect'),
            ], 'wire-tomselect-views');
        }
    }
}
