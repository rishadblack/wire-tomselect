<?php

use Illuminate\View\ViewException;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Rishadblack\WireTomselect\Tests\Fixtures\User;
use Rishadblack\WireTomselect\Tests\Fixtures\UserSelect;

function createUsers(array $names): void
{
    foreach ($names as $name) {
        User::create(['name' => $name, 'email' => strtolower(str_replace(' ', '', $name)).'@example.com']);
    }
}

it('builds the select id from the name', function () {
    Livewire::test(UserSelect::class, ['name' => 'items.0.user_id'])
        ->assertSet('select_id', 'items_0_user_id');
});

it('requires a name when there is no wire:model binding', function () {
    Livewire::test(UserSelect::class);
})->throws(ViewException::class, 'requires a unique "name" attribute or a wire:model binding');

it('reloads the options when a value outside the loaded options is assigned', function () {
    createUsers(array_map(fn (int $index): string => "A User {$index}", range(1, 10)));
    $hidden = User::create(['name' => 'Zz Hidden', 'email' => 'hidden@example.com']);

    $component = Livewire::test(UserSelect::class, ['name' => 'user_id'])
        ->set('value', $hidden->id);

    expect(collect($component->get('data'))->pluck('id'))->toContain($hidden->id);
});

it('keeps the loaded options when the assigned value is already loaded', function () {
    createUsers(['Amy', 'Bob']);
    $component = Livewire::test(UserSelect::class, ['name' => 'user_id']);
    createUsers(['Cat']);

    $component->set('value', User::where('name', 'Bob')->value('id'));

    expect(collect($component->get('data'))->pluck('name')->all())->toBe(['Amy', 'Bob']);
});

it('loads options ordered by label and limited to max options', function () {
    createUsers(['Eve', 'Bob', 'Dan', 'Amy', 'Cat', 'Fay']);

    $component = Livewire::test(UserSelect::class, ['name' => 'user_id']);

    expect(collect($component->get('data'))->pluck('name')->all())
        ->toBe(['Amy', 'Bob', 'Cat', 'Dan', 'Eve']);
});

it('maps options to id and name keys', function () {
    createUsers(['Amy']);

    $component = Livewire::test(UserSelect::class, ['name' => 'user_id']);

    expect($component->get('data'))->toBe([['id' => User::first()->id, 'name' => 'Amy']]);
});

it('searches across all search fields', function () {
    createUsers(['Jane Doe', 'John Roe']);

    $component = Livewire::test(UserSelect::class, ['name' => 'user_id'])
        ->call('searchBuilder', 'janedoe@');

    expect($component->get('data'))->toHaveCount(1)
        ->and($component->get('data.0.name'))->toBe('Jane Doe');
});

it('returns the default list when the search term is blank', function (?string $term) {
    createUsers(['Eve', 'Bob', 'Amy']);

    $component = Livewire::test(UserSelect::class, ['name' => 'user_id'])
        ->call('searchBuilder', $term);

    expect(collect($component->get('data'))->pluck('name')->all())->toBe(['Amy', 'Bob', 'Eve']);
})->with(['null' => [null], 'empty string' => ['']]);

it('returns an empty list when nothing matches', function () {
    createUsers(['Amy']);

    $component = Livewire::test(UserSelect::class, ['name' => 'user_id'])
        ->call('searchBuilder', 'zzz');

    expect($component->get('data'))->toBe([]);
});

it('preloads the selected value even when it is outside the option limit', function () {
    createUsers(array_map(fn (int $index): string => "A User {$index}", range(1, 10)));
    $selected = User::create(['name' => 'Zz Last', 'email' => 'last@example.com']);

    $component = Livewire::test(UserSelect::class, ['name' => 'user_id', 'value' => $selected->id]);

    expect(collect($component->get('data'))->pluck('id'))->toContain($selected->id)
        ->and($component->get('data'))->toHaveCount(6);
});

it('does not duplicate a selected value that is already within the limit', function () {
    createUsers(['Amy', 'Bob']);

    $component = Livewire::test(UserSelect::class, ['name' => 'user_id', 'value' => User::first()->id]);

    expect($component->get('data'))->toHaveCount(2);
});

it('preloads every selected value of a multiple select', function () {
    createUsers(array_map(fn (int $index): string => "A User {$index}", range(1, 10)));
    $first = User::create(['name' => 'Zy First', 'email' => 'zy@example.com']);
    $second = User::create(['name' => 'Zz Second', 'email' => 'zz@example.com']);

    $component = Livewire::test(UserSelect::class, ['name' => 'user_ids', 'multiple' => true, 'value' => [$first->id, $second->id]]);

    expect(collect($component->get('data'))->pluck('id')->all())
        ->toContain($first->id, $second->id);
});

it('loads missing ids on demand', function () {
    createUsers(array_map(fn (int $index): string => "A User {$index}", range(1, 10)));
    $hidden = User::create(['name' => 'Zz Hidden', 'email' => 'hidden@example.com']);

    $component = Livewire::test(UserSelect::class, ['name' => 'user_id'])
        ->call('baseMapWithIds', [$hidden->id]);

    expect(collect($component->get('data'))->pluck('id'))->toContain($hidden->id);
});

it('still accepts a single id through baseMapWithId', function () {
    createUsers(array_map(fn (int $index): string => "A User {$index}", range(1, 10)));
    $hidden = User::create(['name' => 'Zz Hidden', 'email' => 'hidden@example.com']);

    $component = Livewire::test(UserSelect::class, ['name' => 'user_id'])
        ->call('baseMapWithId', $hidden->id);

    expect(collect($component->get('data'))->pluck('id'))->toContain($hidden->id);
});

it('ignores non-scalar and empty ids sent from the browser', function () {
    createUsers(['Amy']);

    $component = Livewire::test(UserSelect::class, ['name' => 'user_id'])
        ->call('baseMapWithIds', [['nested' => 1], '', null, User::first()->id]);

    expect($component->get('data'))->toHaveCount(1);
});

it('never looks up more ids than the option ceiling', function () {
    config()->set('wire-tomselect.max_options_limit', 2);
    createUsers(['Amy', 'Bob', 'Cat', 'Dan']);

    $component = Livewire::test(UserSelect::class, ['name' => 'user_ids', 'multiple' => true, 'max_options' => 1])
        ->set('value', User::pluck('id')->all());

    expect($component->get('data'))->toHaveCount(2);
});

it('truncates very long search terms instead of failing', function () {
    createUsers(['Amy']);

    $component = Livewire::test(UserSelect::class, ['name' => 'user_id'])
        ->call('searchBuilder', str_repeat('a', 5000));

    expect($component->get('data'))->toBe([]);
});

it('ignores unknown ids without failing', function () {
    createUsers(['Amy']);

    $component = Livewire::test(UserSelect::class, ['name' => 'user_id'])
        ->call('baseMapWithIds', [999]);

    expect($component->get('data'))->toHaveCount(1);
});

it('lets a blade attribute override the limit set in configure', function () {
    createUsers(['Eve', 'Bob', 'Amy']);

    $component = Livewire::test(UserSelect::class, ['name' => 'user_id', 'max_options' => 2]);

    expect(collect($component->get('data'))->pluck('name')->all())->toBe(['Amy', 'Bob'])
        ->and($component->get('max_options'))->toBe(2);
});

it('caps the option limit at the configured ceiling', function () {
    config()->set('wire-tomselect.max_options_limit', 3);
    createUsers(['Eve', 'Bob', 'Dan', 'Amy', 'Cat', 'Fay']);

    $component = Livewire::test(UserSelect::class, ['name' => 'user_id']);

    expect($component->get('data'))->toHaveCount(3)
        ->and($component->get('max_options'))->toBe(3);
});

it('rejects client-side changes to the configuration', function (string $property, mixed $value) {
    Livewire::test(UserSelect::class, ['name' => 'user_id'])->set($property, $value);
})->with([
    'max_options' => ['max_options', 1000],
    'search_field' => ['search_field', ['email']],
    'label_field' => ['label_field', 'email'],
    'value_field' => ['value_field', 'email'],
    'searchable' => ['searchable', false],
    'name' => ['name', 'other'],
    'multiple' => ['multiple', true],
])->throws(CannotUpdateLockedPropertyException::class);

it('still accepts the selected value from the client', function () {
    createUsers(['Amy']);

    Livewire::test(UserSelect::class, ['name' => 'user_id'])
        ->set('value', User::first()->id)
        ->assertSet('value', User::first()->id);
});

it('normalises string booleans passed from blade', function () {
    Livewire::test(UserSelect::class, ['name' => 'user_id', 'disabled' => 'true', 'multiple' => 'false'])
        ->assertSet('disabled', true)
        ->assertSet('multiple', false)
        ->assertSeeHtml('disabled')
        ->assertDontSeeHtml('multiple></select>');

    Livewire::test(UserSelect::class, ['name' => 'user_ids', 'multiple' => true])
        ->assertSeeHtml('multiple></select>');
});

it('splits the create load component into action and component', function () {
    Livewire::test(UserSelect::class, ['name' => 'user_id', 'create_load_component' => 'open, customers.create-modal'])
        ->assertSet('create_load', ['open', 'customers.create-modal']);
});

it('leaves the create load empty when no component is given', function () {
    Livewire::test(UserSelect::class, ['name' => 'user_id'])
        ->assertSet('create_load', []);
});
