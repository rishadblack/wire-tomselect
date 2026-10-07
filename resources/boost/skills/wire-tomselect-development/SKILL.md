---
name: wire-tomselect-development
description: "Use this skill when building searchable Livewire select or dropdown fields with rishadblack/wire-tomselect. Trigger when creating or editing classes that extend Rishadblack\\WireTomselect\\SearchComponent, rendering them with wire:model, building dependent (cascading) dropdowns with #[Reactive], using the WithTomselect trait (tomSelectUpdate, tomSelectReset, tomSelectRemoteUpdate), adding 'create new option' flows, showing validation errors on a Tom Select field, wiring the Tom Select JS/CSS assets, or testing these components with Pest."
license: MIT
metadata:
  author: rishadblack
---

# Wire TomSelect Development

`rishadblack/wire-tomselect` wraps [Tom Select](https://tom-select.js.org) in a Livewire component. You write a small PHP class that describes the Eloquent query. The package then renders the `<select>`, runs remote search through Livewire, and keeps the selection in sync with `wire:model`.

## When to Use This Package

- Use it for any select field whose options come from an Eloquent model: users, products, customers, cities, and so on. It is most useful when the table is too large to load in full.
- Do not use it for short, fixed lists such as enums or statuses. A plain `<select>` is simpler for those.

## Requirements and Setup

- PHP 8.3+, Laravel 11–13, Livewire 3 or 4.
- Tom Select must be installed through npm, and the package's bootstrap script must load before Livewire starts:

```bash
npm install tom-select
```

```js
// resources/js/app.js
import '../../vendor/rishadblack/wire-tomselect/resources/js/wire-tomselect-all.js';
```

```css
/* resources/css/app.css */
@import 'tom-select/dist/css/tom-select.css';
```

- The script sets `window.TomSelect` and collects `tom_select_set_value` events that fire before a dropdown exists, for example while a modal is still opening.
- The default view uses Bootstrap classes (`form-group`, `form-label`, `invalid-feedback`). To change the markup, publish the view with `php artisan vendor:publish --provider="Rishadblack\WireTomselect\WireTomselectServiceProvider"` and edit `resources/views/vendor/wire-tomselect/search.blade.php`.

## Creating a Dropdown Component

Create a Livewire component and extend `SearchComponent` instead of `Livewire\Component`. Delete the generated view, because the package provides its own.

```bash
php artisan make:livewire Selects/UserSelect --no-interaction
```

```php
<?php

namespace App\Livewire\Selects;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Rishadblack\WireTomselect\SearchComponent;

class UserSelect extends SearchComponent
{
    public function builder(): Builder
    {
        return User::query()->where('is_active', true);
    }

    public function configure(): void
    {
        $this->isSearchable();
        $this->setSearchField(['name', 'email']);
        $this->setMaxOptions(25);
    }
}
```

Rules:

- Import `Illuminate\Database\Eloquent\Builder`, not the query builder. `builder()` must return an Eloquent builder.
- `configure()` runs on every render and every search. Keep it cheap and limited to configuration. Do not run queries there.
- Do not override `mount()` or `render()` unless you call `parent::mount()` / `parent::render()`. The base `mount()` preloads options and builds the select id.

## Configuration API

Call these inside `configure()`:

| Method | Default | Purpose |
|---|---|---|
| `isSearchable()` | off | Turns on server-side search while the user types. Without it, only the initial options are shown. |
| `setSearchField(array $fields)` | `['name']` | Columns matched with `LIKE %term%`, combined with OR. |
| `setValueField(string $field)` | `id` | Column used as the option value. |
| `setLabelField(string $field)` | `name` | Column used as the option label and the default `ORDER BY`. |
| `setMaxOptions(int $max)` | `20` | Maximum number of options. Applied as `LIMIT` when the component is searchable. |
| `showRemoveButton()` | off | Shows a "×" button on a single select. Multiple selects always show it. |

Behaviour to know:

- If `builder()` has no `orderBy`, the package orders by the label field in ascending order. Add your own `orderBy` to change that.
- If `builder()` has no `limit` and the component is searchable, the package applies `setMaxOptions()` as the limit.
- When the field names contain a table prefix (`users.name`), the prefixed name is used in SQL and only the last segment (`name`) is read from each model. Use this when `builder()` contains joins.

```php
public function builder(): Builder
{
    return Order::query()
        ->join('customers', 'customers.id', '=', 'orders.customer_id')
        ->select('orders.*', 'customers.name as customer_name');
}

public function configure(): void
{
    $this->isSearchable();
    $this->setValueField('orders.id');
    $this->setLabelField('orders.reference');
    $this->setSearchField(['orders.reference', 'customers.name']);
}
```

## Custom Labels and Custom Search

Override `map()` to build labels from several columns. Each item must use the keys `id` and `name`, because the JavaScript always reads those two keys.

```php
use Illuminate\Database\Eloquent\Collection;

public function map(Collection $collection): array
{
    return $collection->map(fn (User $user): array => [
        'id' => $user->id,
        'name' => "{$user->name} ({$user->email})",
    ])->toArray();
}
```

Override `search()` to replace the default `LIKE` matching, for example to use scopes, full-text search, or relationships:

```php
public function search(Builder $query, string $search): Builder
{
    return $query->where(function (Builder $query) use ($search): void {
        $query->where('name', 'like', "{$search}%")
            ->orWhereHas('company', fn (Builder $company) => $company->where('name', 'like', "{$search}%"));
    });
}
```

## Rendering in Blade

The selected value is `#[Modelable]`, so bind it with `wire:model` from the parent component. Always pass a `name` that is unique on the page.

```blade
<livewire:selects.user-select wire:model="user_id" name="user_id" label="Assignee" />
```

Available props:

| Prop | Purpose |
|---|---|
| `name` | **Required.** Builds the DOM id (dots become underscores) and is the key used for updates, resets, and validation errors. Use the same value as the parent property, e.g. `items.0.product_id`. |
| `label` | Label text. If empty, the label is hidden. |
| `label_class`, `class` | Extra CSS classes for the label and the select. |
| `placeholder` | Placeholder text. Defaults to "Type to select {label}". |
| `multiple` | Allows multiple selection. Bind `wire:model` to an array property. |
| `disabled` | Pass `disabled="true"` to disable the field. |
| `max_options` | Overrides the option limit for this instance only. |
| `create_event` | Name of an event to dispatch with `{ text }` when the user types a value that does not exist. |
| `create_load_component` | `"action,component"`. Dispatches `loadComponent` with the typed text so the app can open a creation form, such as a modal (see below). |

Multiple selection example:

```blade
<livewire:selects.tag-select wire:model="tag_ids" name="tag_ids" label="Tags" multiple="true" />
```

## Dependent (Cascading) Dropdowns

Add `#[Reactive]` properties to the dropdown subclass and use them in `builder()`. When the parent changes the value, the package calls `baseMap()` again, replaces the options, and keeps the current selection if it is still valid.

```php
use Livewire\Attributes\Reactive;

class CitySelect extends SearchComponent
{
    #[Reactive]
    public ?int $country_id = null;

    public function builder(): Builder
    {
        return City::query()->where('country_id', $this->country_id);
    }

    public function configure(): void
    {
        $this->isSearchable();
    }
}
```

```blade
<livewire:selects.country-select wire:model.live="country_id" name="country_id" label="Country" />
<livewire:selects.city-select wire:model="city_id" name="city_id" label="City" :country_id="$country_id" />
```

The parent must bind the controlling field with `wire:model.live`, so that the reactive prop is sent to the child as soon as it changes.

## Controlling Dropdowns from a Parent Component

Use the `WithTomselect` trait in the page or form component that contains the dropdowns:

```php
use Rishadblack\WireTomselect\Traits\WithTomselect;

class EditOrder extends Component
{
    use WithTomselect;

    public function prefillCustomer(Customer $customer): void
    {
        $this->customer_id = $customer->id;

        // Add the option if it is not loaded yet, then select it.
        $this->tomSelectUpdate([
            'customer_id' => [
                'value' => $customer->id,
                'options' => ['id' => $customer->id, 'name' => $customer->name],
            ],
        ]);
    }

    public function resetForm(): void
    {
        $this->reset('customer_id', 'city_id');
        $this->tomSelectReset(['customer_id', 'city_id']); // or tomSelectReset() to clear every dropdown
    }
}
```

- The keys passed to `tomSelectUpdate()` and `tomSelectReset()` are the dropdown `name` props.
- If the option is already loaded, a plain value is enough: `tomSelectUpdate(['customer_id' => 5])`.
- Changing the bound `wire:model` property on its own also updates the dropdown. If the value is not among the loaded options, the dropdown fetches it with `baseMapWithId()`. Use `tomSelectUpdate()` when you already have the label and want to avoid that extra request.

## "Create New Option" Flows

**Simple event.** Pass `create_event="customer-create"`. The dropdown dispatches `customer-create` with `['text' => $typed]`. Handle the event, create the record, then select it:

```php
#[On('customer-create')]
public function createCustomer(string $text): void
{
    $customer = Customer::create(['name' => $text]);

    $this->tomSelectUpdate([
        'customer_id' => ['value' => $customer->id, 'options' => ['id' => $customer->id, 'name' => $customer->name]],
    ]);
}
```

**Modal or form component.** Pass `create_load_component="open,customers.create-modal"`. The dropdown dispatches `loadComponent` with `action`, `component`, and `data` (`text`, `field_name`, `extra`). Your app's modal loader must mount that component and pass `data` to it. In that component:

```php
use Rishadblack\WireTomselect\Traits\WithTomselect;

class CreateModal extends Component
{
    use WithTomselect; // mountWithTomselect() prefills $this->name from the typed text and stores the source field in the session

    public string $name = '';

    public function save(): void
    {
        $customer = Customer::create($this->validate(['name' => 'required|string|max:255']));

        // Selects the new record in the dropdown that opened this modal.
        $this->tomSelectRemoteUpdate($customer->id, $customer->name);
    }
}
```

Define `tomSelectText(string $text)` on the component to handle the typed text yourself instead of having it assigned to `$name`.

## Validation Errors

The dropdown shows the error inside its own `invalid-feedback` span when an `alert` event with this shape is dispatched:

```php
$this->dispatch('alert', type: 'error', data: [
    'validation_errors' => $validator->errors()->toArray(), // keys must match the dropdown `name`
]);
```

The error is hidden again when the user changes the selection.

## Testing with Pest

Test dropdown classes directly with `Livewire::test()`. `searchBuilder()` and `baseMap()` return the mapped options, and the `data` property holds them.

```php
use App\Livewire\Selects\UserSelect;
use App\Models\User;
use Livewire\Livewire;

it('searches users by email', function () {
    User::factory()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
    User::factory()->create(['name' => 'John Roe', 'email' => 'john@example.com']);

    $component = Livewire::test(UserSelect::class, ['name' => 'user_id'])
        ->call('searchBuilder', 'jane@');

    expect($component->get('data'))
        ->toHaveCount(1)
        ->and($component->get('data.0.name'))->toBe('Jane Doe');
});

it('preloads the selected user even when outside the option limit', function () {
    User::factory()->count(30)->create();
    $selected = User::factory()->create(['name' => 'Zz Last']);

    $component = Livewire::test(UserSelect::class, ['name' => 'user_id', 'value' => $selected->id]);

    expect(collect($component->get('data'))->pluck('id'))->toContain($selected->id);
});
```

- Test each dropdown's query rules (scopes, reactive filters, custom `map()`/`search()`) at this level.
- For parent components, assert dispatched events, e.g. `->assertDispatched('tom_select_set_value')`. Browser behaviour is Tom Select's responsibility.

## Common Pitfalls

- **Options show the wrong text:** `map()` returned keys other than `id`/`name`.
- **Two dropdowns interfere with each other:** both use the same `name`. Every instance on a page needs a unique `name`.
- **`TomSelect is not defined`:** `wire-tomselect-all.js` is missing or loads after Livewire.
- **Search does nothing:** `isSearchable()` is not called in `configure()`.
- **Child options don't refresh:** the property is not marked `#[Reactive]`, or the parent binds with `wire:model` instead of `wire:model.live`.
- **SQL error about an ambiguous column after a join:** use table-prefixed names in the value, label, and search fields.
