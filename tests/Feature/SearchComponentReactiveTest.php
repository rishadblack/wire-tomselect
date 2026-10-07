<?php

use Livewire\Features\SupportReactiveProps\SupportReactiveProps;
use Livewire\Livewire;
use Rishadblack\WireTomselect\Tests\Fixtures\FilteredUserSelect;
use Rishadblack\WireTomselect\Tests\Fixtures\FilterPage;
use Rishadblack\WireTomselect\Tests\Fixtures\User;

it('filters the options by the reactive prop on mount', function () {
    User::create(['name' => 'Amy', 'email' => 'amy@example.com', 'country_id' => 1]);
    User::create(['name' => 'Bob', 'email' => 'bob@example.com', 'country_id' => 2]);

    $component = Livewire::test(FilteredUserSelect::class, ['name' => 'user_id', 'country_id' => 2]);

    expect(collect($component->get('data'))->pluck('name')->all())->toBe(['Bob']);
});

it('exposes the reactive prop names to the alpine component', function () {
    Livewire::test(FilteredUserSelect::class, ['name' => 'user_id'])
        ->assertSeeHtml('reactiveProps');

    expect((new FilteredUserSelect)->getReactiveProps())->toBe(['country_id']);
});

it('derives the name and select id from the wire:model binding', function () {
    Livewire::test(FilterPage::class)
        ->assertSeeHtml('id="user_id_select"')
        ->assertSeeHtml('id="user_id_group"');
});

it('passes the parent value into the dropdown', function () {
    User::create(['name' => 'Amy', 'email' => 'amy@example.com', 'country_id' => 1]);
    User::create(['name' => 'Bob', 'email' => 'bob@example.com', 'country_id' => 2]);

    Livewire::test(FilterPage::class, ['country_id' => 2])
        ->assertSee('Bob')
        ->assertDontSee('Amy');
});

it('reloads the options when the parent changes the reactive prop', function () {
    User::create(['name' => 'Amy', 'email' => 'amy@example.com', 'country_id' => 1]);
    User::create(['name' => 'Bob', 'email' => 'bob@example.com', 'country_id' => 2]);
    $component = Livewire::test(FilteredUserSelect::class, ['name' => 'user_id', 'country_id' => 1]);

    // Livewire fills this from the parent's render in the same request as the child's commit.
    SupportReactiveProps::$pendingChildParams[$component->instance()->getId()] = ['country_id' => 2];
    $component->call('$refresh');

    expect(collect($component->get('data'))->pluck('name')->all())->toBe(['Bob'])
        ->and($component->get('country_id'))->toBe(2);
});

it('keeps the loaded options when the reactive prop is unchanged', function () {
    User::create(['name' => 'Amy', 'email' => 'amy@example.com', 'country_id' => 1]);
    $component = Livewire::test(FilteredUserSelect::class, ['name' => 'user_id', 'country_id' => 1]);
    User::create(['name' => 'Zed', 'email' => 'zed@example.com', 'country_id' => 1]);

    SupportReactiveProps::$pendingChildParams[$component->instance()->getId()] = ['country_id' => 1];
    $component->call('getReactiveProps'); // any non-renderless call, so the component re-renders

    expect(collect($component->get('data'))->pluck('name')->all())->toBe(['Amy']);
});
