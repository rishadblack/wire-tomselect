# WireTomSelect

Searchable Livewire dropdowns backed by [Tom Select](https://tom-select.js.org).

You write one small PHP class that describes an Eloquent query. The package renders the `<select>`, searches on the server while the user types, keeps the selection in sync with `wire:model`, and drives the browser side with a single Alpine component. You write no JavaScript.

```blade
<livewire:selects.customer-select wire:model="customer_id" label="Customer" />
```

## Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Your first dropdown](#your-first-dropdown)
- [Configuration reference](#configuration-reference)
- [Blade attributes](#blade-attributes)
- [Labels, rich rows and search](#labels-rich-rows-and-search)
- [Joins](#joins)
- [Multiple selection](#multiple-selection)
- [Dependent dropdowns](#dependent-dropdowns)
- [Setting and clearing values from the parent](#setting-and-clearing-values-from-the-parent)
- [Creating new options](#creating-new-options)
- [Validation errors](#validation-errors)
- [Security](#security)
- [Testing](#testing)
- [Customizing the markup](#customizing-the-markup)
- [Troubleshooting](#troubleshooting)
- [Upgrading from 1.x](#upgrading-from-1x)

## Requirements

| Dependency | Version |
|---|---|
| PHP | 8.3 or newer |
| Laravel | 11, 12 or 13 |
| Livewire | 3 or 4 (Alpine is bundled with Livewire) |
| tom-select (npm) | 2.x |

## Installation

**1. Install the PHP package and Tom Select.**

```bash
composer require rishadblack/wire-tomselect
npm install tom-select
```

**2. Load the package script** before Livewire starts. With Vite, import it in `resources/js/app.js`:

```js
import '../../vendor/rishadblack/wire-tomselect/resources/js/wire-tomselect-all.js';
```

The script registers the `wireTomselect` Alpine component. Nothing else is needed in the browser.

**3. Load the Tom Select stylesheet** in `resources/css/app.css`. Pick the theme that matches your CSS framework:

```css
@import 'tom-select/dist/css/tom-select.bootstrap5.css';
/* or the neutral theme: @import 'tom-select/dist/css/tom-select.css'; */
```

**4. Rebuild your assets.**

```bash
npm run build
```

**5. Optionally publish the config file** to change the defaults:

```bash
php artisan vendor:publish --tag=wire-tomselect-config
```

```php
// config/wire-tomselect.php
return [
    'max_options' => 20,        // options loaded per request when a component sets no limit
    'max_options_limit' => 100, // hard ceiling for every dropdown, whatever it asks for
    'load_throttle' => 300,     // milliseconds to wait after the last keystroke before searching
    'min_search_length' => 1,   // characters required before a search request is sent
];
```

## Your first dropdown

**1. Create a Livewire component** and delete the view it generates, because the package provides its own.

```bash
php artisan make:livewire Selects/CustomerSelect
```

**2. Extend `SearchComponent`** and implement two methods. `builder()` returns the base query. `configure()` describes how to search and display it.

```php
<?php

namespace App\Livewire\Selects;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Rishadblack\WireTomselect\SearchComponent;

class CustomerSelect extends SearchComponent
{
    public function builder(): Builder
    {
        return Customer::query()->where('is_active', true);
    }

    public function configure(): void
    {
        $this->isSearchable();
        $this->setSearchField(['name', 'email', 'phone']);
    }
}
```

**3. Render it** from any Livewire component and bind it with `wire:model`:

```php
class CreateInvoice extends Component
{
    public ?int $customer_id = null;

    public function save(): void
    {
        $this->validate(['customer_id' => 'required|exists:customers,id']);

        // ...
    }
}
```

```blade
<livewire:selects.customer-select wire:model="customer_id" label="Customer" />
```

That is the whole setup. The dropdown shows the first 20 active customers ordered by name, searches name, email and phone on the server as the user types, and writes the chosen id to `$customer_id`.

## Configuration reference

Call these methods inside `configure()`. It runs on every render and every search, so keep it to plain configuration and never run queries there.

| Method | Default | What it does |
|---|---|---|
| `isSearchable()` | off | Searches on the server while the user types. Without it, only the initial options are shown. |
| `setSearchField(array $fields)` | `['name']` | Columns matched with `LIKE %term%`, combined with `OR`. |
| `setValueField(string $field)` | `id` | Column used as the option value. |
| `setLabelField(string $field)` | `name` | Column used as the option label and the default `ORDER BY`. |
| `setMaxOptions(int $max)` | config `max_options` | Options loaded per request. Always capped by `max_options_limit`. |
| `showRemoveButton()` | off | Shows a "×" button to clear a single select. Multiple selects always have it. |

Behaviour worth knowing:

- If `builder()` has no `orderBy`, the results are ordered by the label field.
- If `builder()` has no `limit`, the option limit is applied, searchable or not.
- The selected value is always loaded, even when it falls outside the limit.

```php
public function builder(): Builder
{
    // Your own ordering and limit win over the defaults.
    return Product::query()->orderByDesc('sold_count')->limit(10);
}
```

## Blade attributes

| Attribute | Purpose |
|---|---|
| `wire:model` | The parent property that holds the selected value. Use `wire:model.live` when other dropdowns depend on it. |
| `label` | Label text. No label is rendered when it is empty. |
| `placeholder` | Defaults to "Type to select {label}". |
| `multiple` | Allows several selections. Bind `wire:model` to an array. |
| `disabled` | Disables the field. |
| `max_options` | Overrides the limit for this one instance. Still capped by the config ceiling. |
| `class`, `label_class` | Extra CSS classes for the select and the label. |
| `name` | Optional. Defaults to the `wire:model` property. See below. |
| `create_event` | Event dispatched when the user creates a new option. See [Creating new options](#creating-new-options). |
| `create_load_component` | `"action,component"` pair for a modal-based creation flow. |

**About `name`.** Every dropdown needs a unique name. It builds the DOM ids and is the key used by resets, updates and validation errors. It defaults to the bound property, so `wire:model="items.0.product_id"` gets the name `items.0.product_id` and the DOM id `items_0_product_id_select`. Pass `name` only when there is no `wire:model`, or when you need the key to differ from the property.

```blade
{{-- These two are equivalent. --}}
<livewire:selects.product-select wire:model="product_id" />
<livewire:selects.product-select wire:model="product_id" name="product_id" />
```

## Labels, rich rows and search

### Custom label

Override `map()` to build the label from several columns. Every item must use the keys `id` and `name`.

```php
use Illuminate\Database\Eloquent\Collection;

public function map(Collection $collection): array
{
    return $collection->map(fn (Customer $customer): array => [
        'id' => $customer->id,
        'name' => "{$customer->name} ({$customer->city})",
    ])->all();
}
```

### Rich rows with HTML

Add an `html` key to control how a row looks in the dropdown. Render it from a Blade view with the `optionHtml()` helper, so Blade escapes every value. Keep `name` as the plain label: it is the fallback text and what tests read. An optional `item_html` key controls how the selected value looks in the closed control.

```php
public function map(Collection $collection): array
{
    return $collection->map(fn (User $user): array => [
        'id' => $user->id,
        'name' => $user->name,
        'html' => $this->optionHtml('selects.user-option', ['user' => $user]),
        // 'item_html' => $this->optionHtml('selects.user-item', ['user' => $user]),
    ])->all();
}
```

```blade
{{-- resources/views/selects/user-option.blade.php --}}
<div class="d-flex align-items-center py-1">
    <img src="{{ $user->avatar }}" class="rounded-circle me-2" width="32" height="32" alt="">
    <div>
        <div class="fw-semibold">{{ $user->name }}</div>
        <div class="small text-muted">{{ $user->email }}</div>
    </div>
</div>
```

The browser inserts `html` and `item_html` as-is. Always build them with Blade's `{{ }}` and never with `{!! !!}` on user data.

If `map()` reads a relation, eager load it in `builder()`, or every option costs one extra query:

```php
public function builder(): Builder
{
    return User::query()->with('company');
}
```

### Searching columns that are not in the label

Search is decided by `setSearchField()`, not by the label. A dropdown labelled with the name alone still finds users by email:

```php
public function configure(): void
{
    $this->isSearchable();
    $this->setSearchField(['name', 'email']); // typing "karim@" finds Karim
}
```

In searchable mode the browser shows exactly what the server returned. When the search text is cleared or the dropdown closes, the default list comes back.

### Custom search logic

Override `search()` for prefix matching, scopes, relations or full-text search:

```php
public function search(Builder $query, string $search): Builder
{
    return $query->where(function (Builder $query) use ($search): void {
        $query->where('name', 'like', "{$search}%")
            ->orWhereHas('company', fn (Builder $company) => $company->where('name', 'like', "{$search}%"));
    });
}
```

## Joins

When `builder()` joins tables, use table-prefixed field names. The prefix is used in SQL, and only the last segment is read from each row.

```php
class OrderSelect extends SearchComponent
{
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
}
```

## Multiple selection

Pass `multiple` and bind to an array property:

```php
public array $tag_ids = [];
```

```blade
<livewire:selects.tag-select wire:model="tag_ids" label="Tags" :multiple="true" />
```

Every selected value is loaded on render, even those outside the option limit, in a single query.

## Dependent dropdowns

Add `#[Reactive]` properties to the child dropdown and use them in `builder()`. When the parent changes the value, the child reloads its options in the same request.

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

In the parent, bind the controlling field with `wire:model.live` and clear the children when it changes:

```php
use Rishadblack\WireTomselect\Traits\WithTomselect;

class AddressForm extends Component
{
    use WithTomselect;

    public ?int $country_id = null;

    public ?int $city_id = null;

    public function updatedCountryId(): void
    {
        $this->reset('city_id');
        $this->tomSelectReset('city_id');
    }
}
```

```blade
<livewire:selects.country-select wire:model.live="country_id" label="Country" />
<livewire:selects.city-select wire:model="city_id" label="City" :country_id="$country_id" />
```

A child can depend on several parents. Use `->when()` so an empty parent means "no filter":

```php
#[Reactive]
public ?int $country_id = null;

#[Reactive]
public ?int $city_id = null;

public function builder(): Builder
{
    return User::query()
        ->when($this->country_id, fn (Builder $query, int $id) => $query->where('country_id', $id))
        ->when($this->city_id, fn (Builder $query, int $id) => $query->where('city_id', $id));
}
```

## Setting and clearing values from the parent

**To select a value, assign the property.** The dropdown re-renders in the same request. If the option is already loaded it is selected; otherwise the package loads it and selects it. This works for cascades too.

```php
public function loadOrder(Order $order): void
{
    $this->country_id = $order->country_id;
    $this->city_id = $order->city_id; // CitySelect reloads for the new country and selects the city
}
```

**To clear dropdowns, use the `WithTomselect` trait.**

```php
use Rishadblack\WireTomselect\Traits\WithTomselect;

class AddressForm extends Component
{
    use WithTomselect;

    public function clearForm(): void
    {
        $this->reset('country_id', 'city_id');
        $this->tomSelectReset(['country_id', 'city_id']); // or tomSelectReset() for every dropdown
    }
}
```

**To push an option the query cannot return**, for example a record outside `builder()`, use `tomSelectUpdate()` with the label you already have:

```php
$this->tomSelectUpdate([
    'customer_id' => [
        'value' => $customer->id,
        'options' => ['id' => $customer->id, 'name' => $customer->name],
    ],
]);
```

The keys are dropdown names. If the option is already loaded, a plain value is enough: `$this->tomSelectUpdate(['customer_id' => 5])`.

## Creating new options

When the typed text matches nothing, the dropdown can offer an "Add …" row. Two flows are available.

### Simple event

Pass `create_event`. The dropdown dispatches it with the typed text. Create the record and assign the property; the dropdown selects it on its own.

```blade
<livewire:selects.customer-select wire:model="customer_id" label="Customer" create_event="customer-create" />
```

```php
use Illuminate\Support\Str;
use Livewire\Attributes\On;

#[On('customer-create')]
public function createCustomer(string $text): void
{
    $name = Str::of($text)->squish()->limit(255, '')->toString();

    if ($name === '') {
        return;
    }

    $this->authorize('create', Customer::class);

    $this->customer_id = Customer::create(['name' => $name])->id;
}
```

The typed text comes from the browser. Validate it, authorize the action, and never trust it as-is.

### Modal or form component

Pass `create_load_component="action,component"`. The dropdown dispatches a `loadComponent` event:

```js
{
    action: 'open',
    component: 'customers.create-modal',
    data: { text: 'typed text', field_name: 'customer_id', extra: ['open', 'customers.create-modal'] },
}
```

```blade
<livewire:selects.customer-select wire:model="customer_id" label="Customer"
    create_load_component="open,customers.create-modal" />
```

Your app's modal loader mounts that component and passes `data` to it. In the modal, use `WithTomselect`. Its mount hook prefills `$name` from the typed text and remembers which dropdown opened it. After saving, `tomSelectRemoteUpdate()` selects the new record in that dropdown.

```php
use Rishadblack\WireTomselect\Traits\WithTomselect;

class CreateModal extends Component
{
    use WithTomselect;

    public string $name = '';

    public function save(): void
    {
        $customer = Customer::create($this->validate(['name' => 'required|string|max:255']));

        $this->tomSelectRemoteUpdate($customer->id, $customer->name);
    }
}
```

Define `tomSelectText(string $text)` on the modal to handle the typed text yourself instead of having it assigned to `$name`.

## Validation errors

The dropdown shows an error under the field when an `alert` event of this shape is dispatched. The keys must match the dropdown names.

```php
use Illuminate\Support\Facades\Validator;

public function save(): void
{
    $validator = Validator::make(
        ['customer_id' => $this->customer_id],
        ['customer_id' => 'required|exists:customers,id'],
    );

    if ($validator->fails()) {
        $this->dispatch('alert', type: 'error', data: ['validation_errors' => $validator->errors()->toArray()]);

        return;
    }

    // ...
}
```

The error disappears when the user changes the selection.

## Security

- **Configuration cannot be changed from the browser.** Every configuration property, including the limit, the search and label fields and the loaded options, is `#[Locked]`. A tampered request throws `CannotUpdateLockedPropertyException`.
- **`builder()` is your authorization boundary.** Whatever it returns can be listed and searched by anyone who can load the page. Scope it on shared tables:

  ```php
  public function builder(): Builder
  {
      return Project::query()->where('team_id', auth()->user()->current_team_id);
  }
  ```

- **Browser input is bounded.** Ids coming from the browser are reduced to scalars, de-duplicated and capped at `max_options_limit`. Search terms are cut at 255 characters. Search values are always bound parameters.
- **Rich HTML is trusted output.** Build `html` and `item_html` with Blade's escaping, as shown above.

## Testing

### Dropdown components

Test each dropdown's query rules with `Livewire::test()`. The loaded options are in the `data` property.

```php
use App\Livewire\Selects\CustomerSelect;
use App\Models\Customer;
use Livewire\Livewire;

it('searches customers by email', function () {
    Customer::factory()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
    Customer::factory()->create(['name' => 'John Roe', 'email' => 'john@example.com']);

    $customers = Livewire::test(CustomerSelect::class, ['name' => 'customer_id'])
        ->call('searchBuilder', 'jane@')
        ->get('data');

    expect(collect($customers)->pluck('name')->all())->toBe(['Jane Doe']);
});

it('hides inactive customers', function () {
    Customer::factory()->inactive()->create();

    $customers = Livewire::test(CustomerSelect::class, ['name' => 'customer_id'])->get('data');

    expect($customers)->toBe([]);
});

it('filters cities by the reactive country', function () {
    $city = City::factory()->create();
    City::factory()->create();

    $cities = Livewire::test(CitySelect::class, ['name' => 'city_id', 'country_id' => $city->country_id])->get('data');

    expect(collect($cities)->pluck('id')->all())->toBe([$city->id]);
});
```

Pass `name` when testing a dropdown on its own, because there is no parent `wire:model` to derive it from.

### Parent components

Assert the dispatched events with their named `fields` payload:

```php
it('clears the city when the country changes', function () {
    Livewire::test(AddressForm::class)
        ->set('city_id', 5)
        ->set('country_id', 2)
        ->assertSet('city_id', null)
        ->assertDispatched('tom_select_set_reset', fields: ['city_id']);
});
```

### Browser tests with Pest 4

The Tom Select control and options have stable selectors derived from the dropdown name:

| Part | Selector |
|---|---|
| Search input | `#{name}_select-ts-control` |
| Clickable control | `#{name}_class .ts-control` |
| Selected item | `#{name}_class .ts-control .item` |
| An option | `#{name}_class .ts-dropdown .option[data-value="{id}"]` |
| "Add …" row | `#{name}_class .ts-dropdown .create` |

```php
it('selects a customer found by email', function () {
    $customer = Customer::factory()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);

    visit('/invoices/create')
        ->type('#customer_id_select-ts-control', 'jane@')
        ->click("#customer_id_class .ts-dropdown .option[data-value=\"{$customer->id}\"]")
        ->assertSeeIn('#customer_id_class .ts-control .item', 'Jane Doe')
        ->assertNoJavaScriptErrors();
});
```

Open a dropdown that already has a value by clicking `#{name}_class .ts-control`. The selected item covers the search input, so clicking the input itself never succeeds.

## Customizing the markup

The default view uses Bootstrap classes. Publish it to change the markup:

```bash
php artisan vendor:publish --tag=wire-tomselect-views
```

Edit `resources/views/vendor/wire-tomselect/search.blade.php`. Keep the `x-data`, `x-on`, `x-ref="select"` and `wire:ignore` attributes, because the Alpine component depends on them. A Tailwind version only needs different classes on the wrapper, label and error span.

## Troubleshooting

| Symptom | Cause and fix |
|---|---|
| The select renders as a plain `<select>` | `wire-tomselect-all.js` is missing or loads after Livewire starts. Import it in `app.js` and rebuild. |
| "requires a unique name attribute or a wire:model binding" | The dropdown has neither. Add `wire:model`, or `name` when rendering it alone. |
| Two dropdowns change together | Both resolve to the same name. Bind them to different properties or pass distinct `name` values. |
| Typing does nothing | `isSearchable()` is missing from `configure()`. |
| Fewer options than expected | `max_options_limit` in the config is lower than `setMaxOptions()`. |
| A child dropdown does not refresh | The property is not marked `#[Reactive]`, or the parent uses `wire:model` instead of `wire:model.live`. |
| "Ambiguous column" SQL error | `builder()` joins tables. Use table-prefixed value, label and search fields. |
| Options show the wrong text | `map()` returned keys other than `id` and `name`. |
| `CannotUpdateLockedPropertyException` | Something in the browser tried to change configuration. Set it in `configure()` or a Blade attribute. |

## Upgrading from 1.x

Upgrade to 2.0.1 or later. It runs 1.x code unchanged, including subclasses that redeclare properties (`public $max_options = 50;`), overrides without return types (`search()`, `mount()`, `render()`, `baseMap()`), code that reads `$this->search_query` or calls `baseSelectId()`, views published from 1.x, and the per-field browser events.

Only two things need attention:

1. **Remove the facade.** The empty `WireTomselect` facade and class are gone.
2. **Update event assertions in tests.** `tomSelectUpdate()` and `tomSelectReset()` now send a named `fields` payload: `->assertDispatched('tom_select_set_reset', fields: ['city_id'])`.

Two behaviours changed on purpose:

- The option limit now applies to non-searchable dropdowns too, capped at 100 by default.
- Configuration properties are locked against changes from the browser.

Once upgraded, move to the 2.x style at your own pace:

- **Republish the view** if you published it. A 1.x view keeps working, but it carries the old inline script and misses the Alpine component, rich rows and the security fixes in the browser.
- **Drop `name`** where it equals the `wire:model` property.
- **Replace `tomSelectUpdate()`** with a plain property assignment where the query can find the value.
- **Replace the per-field events** (`{select_id}_set_value`, `_set_option`, `_set_reset`) with property assignments and `tomSelectReset()`. They still work but are deprecated.
- **Use the new publish tags**: `wire-tomselect-config` and `wire-tomselect-views`.

See [changelog.md](changelog.md) for the full list.

## License

MIT. See [LICENSE](LICENSE).
