<?php

use Livewire\Livewire;
use Rishadblack\WireTomselect\Tests\Fixtures\OrderForm;
use Rishadblack\WireTomselect\Tests\Fixtures\User;
use Rishadblack\WireTomselect\Tests\Fixtures\UserSelect;
use Rishadblack\WireTomselect\Tests\Fixtures\V1BaseMapSelect;
use Rishadblack\WireTomselect\Tests\Fixtures\V1StyleSelect;

/**
 * Code written against 1.x must keep working on 2.x without changes.
 */
function createNamedUsers(int $count): void
{
    foreach (range(1, $count) as $index) {
        User::create(['name' => sprintf('User %02d', $index), 'email' => "user{$index}@example.com"]);
    }
}

it('loads a 1.x subclass with untyped property redeclarations and untyped overrides', function () {
    createNamedUsers(10);

    $component = Livewire::test(V1StyleSelect::class, ['name' => 'user_id']);

    expect($component->get('data'))->toHaveCount(7)
        ->and($component->get('select_id'))->toBe('user_id');

    $component->assertSeeHtml('placeholder="Pick a user"');
});

it('exposes the current search term to builder() through $search_query', function () {
    V1StyleSelect::$seenSearchQueries = [];
    createNamedUsers(2);

    Livewire::test(V1StyleSelect::class, ['name' => 'user_id'])
        ->call('searchBuilder', 'user2@');

    expect(V1StyleSelect::$seenSearchQueries)->toContain(null, 'user2@');
});

it('searches the columns from a redeclared $search_field', function () {
    createNamedUsers(2);

    $component = Livewire::test(V1StyleSelect::class, ['name' => 'user_id'])
        ->call('searchBuilder', 'user2@');

    expect(collect($component->get('data'))->pluck('name')->all())->toBe(['User 02']);
});

it('honours a baseMap() override on mount and on search', function () {
    createNamedUsers(2);

    $component = Livewire::test(V1BaseMapSelect::class, ['name' => 'user_id']);

    expect(collect($component->get('data'))->pluck('name')->last())->toBe('Unassigned');

    $component->call('searchBuilder', 'User 01');

    expect(collect($component->get('data'))->pluck('name')->all())->toBe(['User 01', 'Unassigned']);
});

it('accepts max_options passed as a string from blade', function () {
    createNamedUsers(5);

    $component = Livewire::test(UserSelect::class, ['name' => 'user_id', 'max_options' => '2']);

    expect($component->get('data'))->toHaveCount(2);
});

it('renders a view published from 1.x', function () {
    view()->prependNamespace('wire-tomselect', __DIR__.'/../Fixtures/v1-views');
    createNamedUsers(2);

    Livewire::test(UserSelect::class, ['name' => 'user_id'])
        ->assertSeeHtml('<select type="text" wire:model.change="value" id="user_id_select"')
        ->assertDontSeeHtml('x-data="wireTomselect(');
});

it('accepts option data written back by the 1.x inline script', function () {
    createNamedUsers(1);

    Livewire::test(UserSelect::class, ['name' => 'user_id'])
        ->set('data', [['id' => 1, 'name' => 'User 01', '$order' => 1]])
        ->assertHasNoErrors();
});

it('still listens for the 1.x per-field browser events', function () {
    Livewire::test(UserSelect::class, ['name' => 'items.0.user_id'])
        ->assertSeeHtml('x-on:items_0_user_id_set_value.window')
        ->assertSeeHtml('x-on:items_0_user_id_set_option.window')
        ->assertSeeHtml('x-on:items_0_user_id_set_reset.window');
});

it('accepts the 1.x named arguments of the trait helpers', function () {
    Livewire::test(OrderForm::class)
        ->call('tomSelectUpdate', options: ['customer_id' => 5])
        ->assertDispatched('tom_select_set_value', fields: ['customer_id' => 5])
        ->call('tomSelectReset', options: ['customer_id'])
        ->assertDispatched('tom_select_set_reset', fields: ['customer_id']);
});
