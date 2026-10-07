---
name: wire-tomselect-development
description: Use this skill when building searchable Livewire select or dropdown fields with rishadblack/wire-tomselect. Trigger when creating or editing classes that extend Rishadblack\WireTomselect\SearchComponent, rendering them with wire:model, building dependent (cascading) dropdowns with #[Reactive], using the WithTomselect trait (tomSelectUpdate, tomSelectReset, tomSelectRemoteUpdate), adding 'create new option' flows, showing validation errors on a Tom Select field, wiring the Tom Select JS/CSS assets, or testing these components with Pest.
---

# Wire TomSelect Development

`rishadblack/wire-tomselect` wraps [Tom Select](https://tom-select.js.org) in a Livewire component. You write a small PHP class that describes the Eloquent query. The package then renders the `<select>`, runs remote search through Livewire, keeps the selection in sync with `wire:model`, and drives the browser side with one Alpine component (`wireTomselect`) shipped in the package's JavaScript file.

## When to Use This Package

- Use it for any select field whose options come from an Eloquent model: users, products, customers, cities, and so on. It is most useful when the table is too large to load in full.
- Do not use it for short, fixed lists such as enums or statuses. A plain `<select>` is simpler for those.

## Requirements and Setup

- PHP 8.3+, Laravel 11–13, Livewire 3 or 4. Alpine comes bundled with Livewire; do not install it separately.
- Tom Select must be installed through npm, and the package's script must load before Livewire starts:

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

- The script registers the `wireTomselect` Alpine component on `alpine:init` and buffers `tom_select_set_value` events that fire before a dropdown exists, for example while a modal is still opening. Do not write Tom Select JavaScript in views.
- The default view uses Bootstrap classes (`form-group`, `form-label`, `invalid-feedback`). To change the markup, publish the view with `php artisan vendor:publish --tag=wire-tomselect-views` and edit `resources/views/vendor/wire-tomselect/search.blade.php`. Keep the `x-data`, `x-ref="select"` and `x-on` attributes, because the Alpine component depends on them.
- Publish the config with `php artisan vendor:publish --tag=wire-tomselect-config`. Keys: `max_options` (default limit, 20), `max_options_limit` (hard ceiling, 100), `load_throttle` (ms, 300), `min_search_length` (1).

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
- Do not override `mount()` or `render()` unless you call `parent::mount()` / `parent::render()`. The base `mount()` validates the name, preloads options and builds the select id.
- Every configuration property (`max_options`, `searchable`, `value_field`, `label_field`, `search_field`, `name`, `multiple`, `create_*`) is `#[Locked]`. Set them from `configure()` or Blade attributes only; the browser cannot change them.
- `builder()` is the only authorization boundary. Anything it returns can be listed or searched by whoever can load the page, so scope it to the current user or tenant (`->where('team_id', auth()->user()->team_id)`) when the table is shared.
- When `map()` reads relations, eager load them in `builder()` (`User::query()->with('company')`). Otherwise every option costs one extra query.
- Ids coming from the browser (the bound value, `baseMapWithIds()`) are reduced to scalars and capped at `max_options_limit`; search terms are cut at 255 characters. Override `search()` if you need a different cap.

## Configuration API

Call these inside `configure()`:

| Method | Default | Purpose |
|---|---|---|
| `isSearchable()` | off | Turns on server-side search while the user types. Without it, only the initial options are shown. |
| `setSearchField(array $fields)` | `['name']` | Columns matched with `LIKE %term%`, combined with OR. |
| `setValueField(string $field)` | `id` | Column used as the option value. |
| `setLabelField(string $field)` | `name` | Column used as the option label and the default `ORDER BY`. |
| `setMaxOptions(int $max)` | config `max_options` (20) | Options loaded per request. Always capped by config `max_options_limit` (100). |
| `showRemoveButton()` | off | Shows a "×" button on a single select. Multiple selects always show it. |

Behaviour to know:

- If `builder()` has no `orderBy`, the package orders by the label field in ascending order. Add your own `orderBy` to change that.
- If `builder()` has no `limit`, the package applies the option limit, whether or not the component is searchable. Tom Select never renders more than that anyway.
- The selected value is always loaded, even when it falls outside the limit. One extra `whereIn` query fetches every missing selected id.
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

The selected value is `#[Modelable]`, so bind it with `wire:model` from the parent component. The dropdown's `name` defaults to the bound property, so `name` is only needed when there is no `wire:model` or when the name must differ from it. Mounting with neither throws.

```blade
<livewire:selects.user-select wire:model="user_id" label="Assignee" />
```

Available attributes:

| Attribute | Purpose |
|---|---|
| `name` | Defaults to the `wire:model` property (`items.0.product_id` becomes the id `items_0_product_id`). Builds the DOM id and is the key used for updates, resets, and validation errors. Pass it only when there is no `wire:model` or the key must differ. |
| `label` | Label text. If empty, no label is rendered. |
| `label_class`, `class` | Extra CSS classes for the label and the select. |
| `placeholder` | Placeholder text. Defaults to "Type to select {label}". |
| `multiple` | Allows multiple selection. Bind `wire:model` to an array property. `:multiple="true"` or `multiple="true"` both work. |
| `disabled` | Disables the field. `:disabled="true"` or `disabled="true"` both work. |
| `max_options` | Overrides the option limit for this instance only (still capped by the config ceiling). |
| `create_event` | Name of an event to dispatch with `{ text }` when the user types a value that does not exist. |
| `create_load_component` | `"action,component"`. Dispatches `loadComponent` with the typed text so the app can open a creation form, such as a modal (see below). |

Multiple selection example:

```blade
<livewire:selects.tag-select wire:model="tag_ids" label="Tags" :multiple="true" />
```

## Dependent (Cascading) Dropdowns

Add `#[Reactive]` properties to the dropdown subclass and use them in `builder()`. When the parent changes the value, the child re-renders with fresh options in the same request, and the Alpine component swaps them in. No second request is made.

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
<livewire:selects.country-select wire:model.live="country_id" label="Country" />
<livewire:selects.city-select wire:model="city_id" label="City" :country_id="$country_id" />
```

The parent must bind the controlling field with `wire:model.live`, so that the reactive prop is sent to the child as soon as it changes. The current selection is kept in the browser if it is still valid; clear it from the parent with `tomSelectReset()` when it is not.

## Controlling Dropdowns from a Parent Component

**Assigning the bound property is enough.** When a parent action sets `$this->customer_id = 5`, the dropdown is re-rendered in the same request: if the option is already loaded it is selected, otherwise the package loads it with the query and selects it. No event and no extra request is needed, including for cascaded dropdowns whose reactive props change at the same time.

```php
public function prefill(Order $order): void
{
    $this->country_id = $order->country_id;
    $this->city_id = $order->city_id; // CitySelect reloads for the new country and selects the city
}
```

Use the `WithTomselect` trait for the cases a plain assignment cannot cover: clearing dropdowns, or selecting a value with a label you already have that the dropdown's query would not return:

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

- The keys passed to `tomSelectUpdate()` and `tomSelectReset()` are the dropdown `name` attributes.
- If the option is already loaded, a plain value is enough: `tomSelectUpdate(['customer_id' => 5])`.
- `tomSelectUpdate()` is optional. A plain property assignment already loads and selects the value; use the event only when the value cannot be found by `builder()`.
- Both methods dispatch browser events (`tom_select_set_value`, `tom_select_set_reset`) with a named `fields` payload. Do not dispatch them by hand.

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
    use WithTomselect; // mountWithTomselect() prefills $this->name from the typed text and remembers the source dropdown in $tom_select_field

    public string $name = '';

    public function save(): void
    {
        $customer = Customer::create($this->validate(['name' => 'required|string|max:255']));

        // Selects the new record in the dropdown that opened this modal.
        $this->tomSelectRemoteUpdate($customer->id, $customer->name);
    }
}
```

Define `tomSelectText(string $text)` on the component to handle the typed text yourself instead of having it assigned to `$name`. Nothing is stored in the session.

## Validation Errors

The dropdown shows the error under the field when an `alert` event with this shape is dispatched:

```php
$this->dispatch('alert', type: 'error', data: [
    'validation_errors' => $validator->errors()->toArray(), // keys must match the dropdown `name`
]);
```

The error is hidden again when the user changes the selection.

## Testing with Pest

Test dropdown classes directly with `Livewire::test()`. `searchBuilder()`, `baseMap()` and `baseMapWithIds()` return the mapped options, and the `data` property holds them.

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
- For parent components, assert the dispatched events with their named payload, e.g. `->assertDispatched('tom_select_set_reset', fields: ['city_id'])`. Browser behaviour is Tom Select's responsibility.

## Common Pitfalls

- **Options show the wrong text:** `map()` returned keys other than `id`/`name`.
- **Two dropdowns interfere with each other:** both resolve to the same `name`. Every instance on a page needs a unique bound property or an explicit `name`.
- **`wireTomselect is not defined` or the select stays plain:** `wire-tomselect-all.js` is missing or loads after Livewire.
- **Search does nothing:** `isSearchable()` is not called in `configure()`.
- **Fewer options than expected:** the config ceiling `max_options_limit` is lower than `setMaxOptions()`.
- **Child options don't refresh:** the property is not marked `#[Reactive]`, or the parent binds with `wire:model` instead of `wire:model.live`.
- **SQL error about an ambiguous column after a join:** use table-prefixed names in the value, label, and search fields.
- **"requires a unique name attribute or a wire:model binding":** the dropdown was rendered without `wire:model` and without `name`.
- **`CannotUpdateLockedPropertyException`:** something in the browser tried to set a configuration property. Set it from `configure()` or a Blade attribute instead.
