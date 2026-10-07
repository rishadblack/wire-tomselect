<?php

use Livewire\Livewire;
use Rishadblack\WireTomselect\Tests\Fixtures\CustomUserSelect;
use Rishadblack\WireTomselect\Tests\Fixtures\EmailValueSelect;
use Rishadblack\WireTomselect\Tests\Fixtures\LegacySearchSelect;
use Rishadblack\WireTomselect\Tests\Fixtures\Post;
use Rishadblack\WireTomselect\Tests\Fixtures\PostSelect;
use Rishadblack\WireTomselect\Tests\Fixtures\RankedSelect;
use Rishadblack\WireTomselect\Tests\Fixtures\RichUserSelect;
use Rishadblack\WireTomselect\Tests\Fixtures\User;

it('uses the overridden map for labels', function () {
    User::create(['name' => 'Amy', 'email' => 'amy@example.com']);

    $component = Livewire::test(CustomUserSelect::class, ['name' => 'user_id']);

    expect($component->get('data.0.name'))->toBe('Amy <amy@example.com>');
});

it('uses the overridden search instead of the default like query', function () {
    User::create(['name' => 'Amy Pond', 'email' => 'amy@example.com']);
    User::create(['name' => 'Sam Amy', 'email' => 'sam@example.com']);

    $component = Livewire::test(CustomUserSelect::class, ['name' => 'user_id'])
        ->call('searchBuilder', 'Amy');

    expect(collect($component->get('data'))->pluck('name')->all())->toBe(['Amy Pond <amy@example.com>']);
});

it('passes rendered html through to the option while keeping a plain label', function () {
    User::create(['name' => 'Amy', 'email' => 'amy@example.com']);

    $component = Livewire::test(RichUserSelect::class, ['name' => 'user_id']);

    expect($component->get('data.0.name'))->toBe('Amy')
        ->and(trim($component->get('data.0.html')))->toBe('<div class="row"><strong>Amy</strong> <span>amy@example.com</span></div>');
});

it('escapes model values inside the rendered option html', function () {
    User::create(['name' => '<b>Amy</b>', 'email' => 'amy@example.com']);

    $component = Livewire::test(RichUserSelect::class, ['name' => 'user_id']);

    expect($component->get('data.0.html'))->toContain('&lt;b&gt;Amy&lt;/b&gt;')
        ->not->toContain('<b>Amy</b>');
});

it('finds options by a column that is not part of the label', function () {
    User::create(['name' => 'Amy', 'email' => 'amy@example.com']);
    User::create(['name' => 'Bob', 'email' => 'bob@example.com']);

    $component = Livewire::test(RichUserSelect::class, ['name' => 'user_id'])
        ->call('searchBuilder', 'bob@');

    expect(collect($component->get('data'))->pluck('name')->all())->toBe(['Bob']);
});

it('runs a search override written without a return type', function () {
    User::create(['name' => 'Amy', 'email' => 'amy@example.com']);
    User::create(['name' => 'Bob', 'email' => 'bob@example.com']);

    $component = Livewire::test(LegacySearchSelect::class, ['name' => 'user_id'])
        ->call('searchBuilder', 'bob');

    expect(collect($component->get('data'))->pluck('name')->all())->toBe(['Bob']);
});

it('trims long search terms before they reach a search override', function () {
    Livewire::test(LegacySearchSelect::class, ['name' => 'user_id'])
        ->call('searchBuilder', str_repeat('a', 5000));

    expect(LegacySearchSelect::$receivedSearch)->toHaveLength(LegacySearchSelect::MAX_SEARCH_LENGTH);
});

it('does not call the search override for a blank term', function () {
    LegacySearchSelect::$receivedSearch = null;
    User::create(['name' => 'Amy', 'email' => 'amy@example.com']);

    $component = Livewire::test(LegacySearchSelect::class, ['name' => 'user_id'])
        ->call('searchBuilder', '');

    expect(LegacySearchSelect::$receivedSearch)->toBeNull()
        ->and($component->get('data'))->toHaveCount(1);
});

it('uses the configured value field as the option id', function () {
    User::create(['name' => 'Amy', 'email' => 'amy@example.com']);

    $component = Livewire::test(EmailValueSelect::class, ['name' => 'email']);

    expect($component->get('data'))->toBe([['id' => 'amy@example.com', 'name' => 'Amy']]);
});

it('preloads a selected value through a custom value field', function () {
    foreach (['Amy', 'Bob', 'Cat', 'Dan'] as $name) {
        User::create(['name' => $name, 'email' => strtolower($name).'@example.com']);
    }

    $component = Livewire::test(EmailValueSelect::class, ['name' => 'email', 'value' => 'dan@example.com']);

    expect(collect($component->get('data'))->pluck('id')->all())
        ->toBe(['amy@example.com', 'bob@example.com', 'cat@example.com', 'dan@example.com']);
});

it('ignores the search term when the component is not searchable', function () {
    User::create(['name' => 'Amy', 'email' => 'amy@example.com']);
    User::create(['name' => 'Bob', 'email' => 'bob@example.com']);

    $component = Livewire::test(EmailValueSelect::class, ['name' => 'email'])
        ->call('searchBuilder', 'Bob');

    expect($component->get('data'))->toHaveCount(2);
});

it('still limits a non-searchable component', function () {
    foreach (['Amy', 'Bob', 'Cat', 'Dan', 'Eve'] as $name) {
        User::create(['name' => $name, 'email' => strtolower($name).'@example.com']);
    }

    $component = Livewire::test(EmailValueSelect::class, ['name' => 'email']);

    expect($component->get('data'))->toHaveCount(3);
});

it('keeps the ordering and limit defined in the builder', function () {
    foreach (['Amy', 'Bob', 'Cat'] as $name) {
        User::create(['name' => $name, 'email' => strtolower($name).'@example.com']);
    }

    $component = Livewire::test(RankedSelect::class, ['name' => 'user_id']);

    expect(collect($component->get('data'))->pluck('name')->all())->toBe(['Cat', 'Bob']);
});

it('reads table-prefixed fields from joined queries', function () {
    $author = User::create(['name' => 'Amy', 'email' => 'amy@example.com']);
    $post = Post::create(['user_id' => $author->id, 'title' => 'Hello']);

    $component = Livewire::test(PostSelect::class, ['name' => 'post_id', 'value' => $post->id]);

    expect($component->get('data'))->toBe([['id' => $post->id, 'name' => 'Hello']]);
});

it('searches joined columns without ambiguous column errors', function () {
    $amy = User::create(['name' => 'Amy', 'email' => 'amy@example.com']);
    $bob = User::create(['name' => 'Bob', 'email' => 'bob@example.com']);
    Post::create(['user_id' => $amy->id, 'title' => 'First']);
    Post::create(['user_id' => $bob->id, 'title' => 'Second']);

    $component = Livewire::test(PostSelect::class, ['name' => 'post_id'])
        ->call('searchBuilder', 'Bob');

    expect(collect($component->get('data'))->pluck('name')->all())->toBe(['Second']);
});
