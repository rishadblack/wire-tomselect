<?php

namespace Rishadblack\WireTomselect;

use Illuminate\Support\ServiceProvider;

class WireTomselectServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(): void
    {
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'rishadblack');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'rishadblack');
        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        // $this->loadRoutesFrom(__DIR__.'/routes.php');

        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/wire-tomselect.php', 'wire-tomselect');

        // Register the service the package provides.
        $this->app->singleton('wire-tomselect', function ($app) {
            return new WireTomselect;
        });

        // Load views from package
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'wire-tomselect');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['wire-tomselect'];
    }

    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole(): void
    {
        // Publishing the configuration file.
        $this->publishes([
            __DIR__ . '/../config/wire-tomselect.php' => config_path('wire-tomselect.php'),
        ], 'wire-tomselect.config');

        // Publish views
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/wire-tomselect'),
        ], 'views');

        // Publishing the views.
        /*$this->publishes([
        __DIR__.'/../resources/views' => base_path('resources/views/vendor/rishadblack'),
        ], 'wire-tomselect.views');*/

        // Publishing assets.
        /*$this->publishes([
        __DIR__.'/../resources/assets' => public_path('vendor/rishadblack'),
        ], 'wire-tomselect.assets');*/

        // Publishing the translation files.
        /*$this->publishes([
        __DIR__.'/../resources/lang' => resource_path('lang/vendor/rishadblack'),
        ], 'wire-tomselect.lang');*/

        // Registering package commands.
        // $this->commands([]);
    }
}
