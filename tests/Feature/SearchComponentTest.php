<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Rishadblack\WireTomselect\Tests\Fixtures\User;
use Rishadblack\WireTomselect\Tests\Fixtures\UserSelect;

beforeEach(function () {
    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email');
    });
});

it('builds the select id from the name', function () {
    Livewire::test(UserSelect::class, ['name' => 'items.0.user_id'])
        ->assertSet('select_id', 'items_0_user_id');
});

it('loads options ordered by label and limited to max options', function () {
    foreach (['Eve', 'Bob', 'Dan', 'Amy', 'Cat', 'Fay'] as $name) {
        User::create(['name' => $name, 'email' => strtolower($name).'@example.com']);
    }

    $component = Livewire::test(UserSelect::class, ['name' => 'user_id']);

    expect(collect($component->get('data'))->pluck('name')->all())
        ->toBe(['Amy', 'Bob', 'Cat', 'Dan', 'Eve']);
});

it('searches across all search fields', function () {
    User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
    User::create(['name' => 'John Roe', 'email' => 'john@example.com']);

    $component = Livewire::test(UserSelect::class, ['name' => 'user_id'])
        ->call('searchBuilder', 'jane@');

    expect($component->get('data'))->toHaveCount(1)
        ->and($component->get('data.0.name'))->toBe('Jane Doe');
});

it('preloads the selected value even when it is outside the option limit', function () {
    foreach (range(1, 10) as $index) {
        User::create(['name' => "A User {$index}", 'email' => "user{$index}@example.com"]);
    }
    $selected = User::create(['name' => 'Zz Last', 'email' => 'last@example.com']);

    $component = Livewire::test(UserSelect::class, ['name' => 'user_id', 'value' => $selected->id]);

    expect(collect($component->get('data'))->pluck('id'))->toContain($selected->id);
});
