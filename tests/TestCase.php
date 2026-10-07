<?php

namespace Rishadblack\WireTomselect\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Rishadblack\WireTomselect\Tests\Fixtures\FilteredUserSelect;
use Rishadblack\WireTomselect\WireTomselectServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * @return array<int, class-string<ServiceProvider>>
     */
    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            WireTomselectServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('database.default', 'testing');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->createTables();
        view()->addNamespace('wire-tomselect', [__DIR__.'/Fixtures/views', __DIR__.'/../resources/views']);
        Livewire::component('filtered-user-select', FilteredUserSelect::class);
    }

    protected function createTables(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->unsignedBigInteger('country_id')->nullable();
        });

        Schema::create('posts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('title');
        });
    }
}
