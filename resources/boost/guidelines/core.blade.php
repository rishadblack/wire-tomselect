## Wire TomSelect

- `rishadblack/wire-tomselect` provides searchable Livewire dropdowns backed by Tom Select. Activate the `wire-tomselect-development` skill whenever you create, edit, or test a dropdown component built on this package.
- Every dropdown is a Livewire component that extends `Rishadblack\WireTomselect\SearchComponent` and implements `builder(): Illuminate\Database\Eloquent\Builder` and `configure(): void`. Do not hand-roll `<select>` + Tom Select JavaScript in views; the package ships an Alpine component (`wireTomselect`) that does this.
- Call configuration helpers (`isSearchable()`, `setSearchField()`, `setValueField()`, `setLabelField()`, `setMaxOptions()`, `showRemoveButton()`) inside `configure()`, not in `mount()`; `configure()` runs on every search and render. These properties are `#[Locked]` and cannot be changed from the browser.
- `map()` must return items shaped as `['id' => ..., 'name' => ...]`. The frontend always reads `id` and `name`, regardless of the value and label fields.
- Bind every dropdown with `wire:model`; its `name` defaults to the bound property and is used to build DOM ids and to target resets and validation errors. Pass `name` only when there is no `wire:model` or the key must differ.
- For dependent dropdowns, add `#[Livewire\Attributes\Reactive]` properties to the subclass and use them in `builder()`. The options reload automatically when the parent changes them.
- To select a value from a parent, assign the bound property (`$this->city_id = 5`); the dropdown loads and selects it in the same request. Use the `Rishadblack\WireTomselect\Traits\WithTomselect` trait (`tomSelectReset()`, `tomSelectUpdate()`) to clear dropdowns or to push a label the query cannot return. Do not dispatch the raw `tom_select_*` browser events by hand.
- The option limit is always applied and capped by `config('wire-tomselect.max_options_limit')`. Tune defaults in `config/wire-tomselect.php`.
- `builder()` is the authorization boundary: scope it to the current user or tenant on shared tables, and eager load relations that `map()` reads.
- Each dropdown needs the `tom-select` npm package installed and the package's `resources/js/wire-tomselect-all.js` loaded before Livewire starts.
