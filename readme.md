# WireTomSelect

Searchable Livewire dropdowns backed by [Tom Select](https://tom-select.js.org). You write a small PHP class that describes the Eloquent query; the package renders the `<select>`, runs remote search through Livewire, keeps the selection in sync with `wire:model`, and wires the browser side with a single Alpine component.

## Requirements

- PHP 8.3+
- Laravel 11, 12 or 13
- Livewire 3 or 4 (Alpine is bundled with Livewire)
- `tom-select` installed through npm

## Installation

```bash
composer require rishadblack/wire-tomselect
npm install tom-select
```

Load the package script before Livewire starts and import the Tom Select stylesheet:

```js
// resources/js/app.js
import '../../vendor/rishadblack/wire-tomselect/resources/js/wire-tomselect-all.js';
```

```css
/* resources/css/app.css */
@import 'tom-select/dist/css/tom-select.css';
```

The script registers the `wireTomselect` Alpine component on `alpine:init`. Nothing else is needed in the browser.

Optionally publish the config or the view:

```bash
php artisan vendor:publish --tag=wire-tomselect-config
php artisan vendor:publish --tag=wire-tomselect-views
```

## Usage

### 1. Create a dropdown component

Extend `SearchComponent` instead of `Livewire\Component`. The package provides the view.

```php
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

### 2. Render it

```blade
<livewire:selects.user-select wire:model="user_id" label="Assignee" />
```

The selected value is `#[Modelable]`, so bind it with `wire:model` from the parent. The dropdown's `name` defaults to the bound property; pass `name` only when there is no `wire:model` or the key must differ.

## Configuration

Call these inside `configure()`:

| Method | Default | Purpose |
|---|---|---|
| `isSearchable()` | off | Server-side search while the user types. |
| `setSearchField(array $fields)` | `['name']` | Columns matched with `LIKE %term%`, combined with OR. |
| `setValueField(string $field)` | `id` | Column used as the option value. |
| `setLabelField(string $field)` | `name` | Column used as the label and the default `ORDER BY`. |
| `setMaxOptions(int $max)` | config `max_options` (20) | Options loaded per request. Always capped by config `max_options_limit` (100). |
| `showRemoveButton()` | off | Shows a "×" button on a single select. |

Table-prefixed names (`users.name`) are used as-is in SQL, and only the last segment is read from the model, which makes joins work.

`builder()` is the only authorization boundary: whatever it returns can be listed and searched by anyone who can load the page, so scope it to the current user or tenant on shared tables. Eager load any relation that `map()` reads.

Override `map()` to build labels from several columns (items must use the keys `id` and `name`), and `search()` to replace the default `LIKE` matching.

For rich rows, add an `html` key (and optionally `item_html` for the selected item) rendered from a Blade view with `$this->optionHtml('selects.user-option', ['user' => $user])`. Blade escapes the values; the browser inserts the HTML as-is. Search is driven by `setSearchField()`, so a name-only label still finds rows by email, and the browser shows exactly what the server returned.

`config/wire-tomselect.php`:

```php
return [
    'max_options' => 20,        // default limit
    'max_options_limit' => 100, // hard ceiling for every dropdown
    'load_throttle' => 300,     // ms to wait after the last keystroke
    'min_search_length' => 1,   // characters required before searching
];
```

## Blade attributes

| Attribute | Purpose |
|---|---|
| `name` | Defaults to the `wire:model` property. Builds the DOM id and targets updates, resets and validation errors. |
| `label`, `label_class`, `class` | Label text and extra CSS classes. The label is omitted when empty. |
| `placeholder` | Defaults to "Type to select {label}". |
| `multiple` | Multiple selection. Bind `wire:model` to an array. |
| `disabled` | Disables the field. |
| `max_options` | Overrides the limit for this instance (still capped by the config ceiling). |
| `create_event` | Event dispatched with `{ text }` when the user types a value that does not exist. |
| `create_load_component` | `"action,component"` dispatched through `loadComponent` to open a creation form. |

## Dependent dropdowns

Add `#[Reactive]` properties and use them in `builder()`. When the parent changes the value, the options are reloaded during the same request and swapped in the browser.

```php
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

## Controlling dropdowns from a parent

Assigning the bound property is enough. The dropdown re-renders in the same request, loads the value if it is not among the loaded options, and selects it:

```php
public function prefill(Customer $customer): void
{
    $this->customer_id = $customer->id;
}
```

Use the `WithTomselect` trait to clear dropdowns, or to select a value with a label you already have:

```php
use Rishadblack\WireTomselect\Traits\WithTomselect;

class EditOrder extends Component
{
    use WithTomselect;

    public function prefill(Customer $customer): void
    {
        $this->customer_id = $customer->id;

        $this->tomSelectUpdate([
            'customer_id' => ['value' => $customer->id, 'options' => ['id' => $customer->id, 'name' => $customer->name]],
        ]);
    }

    public function resetForm(): void
    {
        $this->reset('customer_id', 'city_id');
        $this->tomSelectReset(['customer_id', 'city_id']); // tomSelectReset() clears every dropdown
    }
}
```


## Creating options from the dropdown

Pass `create_event="customer-create"` and handle it:

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

Or pass `create_load_component="open,customers.create-modal"`. The dropdown dispatches `loadComponent` with `action`, `component` and `data` (`text`, `field_name`, `extra`). In the modal component, use `WithTomselect`: `mountWithTomselect()` prefills `$name` (or calls your `tomSelectText()`), and `tomSelectRemoteUpdate($id, $name)` selects the new record in the dropdown that opened the modal.

## Validation errors

The dropdown shows an error under the field when an `alert` event with this shape is dispatched:

```php
$this->dispatch('alert', type: 'error', data: ['validation_errors' => $validator->errors()->toArray()]);
```

## Testing

```php
$component = Livewire::test(UserSelect::class, ['name' => 'user_id'])->call('searchBuilder', 'jane@');

expect($component->get('data'))->toHaveCount(1);

Livewire::test(EditOrder::class)
    ->call('resetForm')
    ->assertDispatched('tom_select_set_reset', fields: ['customer_id', 'city_id']);
```

## Upgrading from 1.x

See [changelog.md](changelog.md). The main points: configuration properties are locked, the option limit always applies and is capped, events carry a named `fields` payload, the facade is gone, and the per-field `{select_id}_set_*` browser events were removed.

## License

MIT. See [LICENSE](LICENSE).
