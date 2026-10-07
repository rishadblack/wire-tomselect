## Wire TomSelect

- `rishadblack/wire-tomselect` provides searchable Livewire dropdowns backed by Tom Select. Activate the `wire-tomselect-development` skill whenever you create, edit, or test a dropdown component built on this package.
- Every dropdown is a Livewire component that extends `Rishadblack\WireTomselect\SearchComponent` and implements `builder(): Illuminate\Database\Eloquent\Builder` and `configure(): void`. Do not hand-roll `<select>` + Tom Select JavaScript in views.
- Call configuration helpers (`isSearchable()`, `setSearchField()`, `setValueField()`, `setLabelField()`, `setMaxOptions()`, `showRemoveButton()`) inside `configure()`, not in `mount()`; `configure()` runs on every search and render.
- `map()` must return items shaped as `['id' => ..., 'name' => ...]`. The frontend always reads `id` and `name`, regardless of the value and label fields.
- Always pass a unique `name` when rendering a dropdown, and bind it with `wire:model`. The `name` is used to build DOM ids and to target values, resets, and validation errors.
- For dependent dropdowns, add `#[Livewire\Attributes\Reactive]` properties to the subclass and use them in `builder()`. The options reload automatically when the parent changes them.
- In parent components, use the `Rishadblack\WireTomselect\Traits\WithTomselect` trait (`tomSelectUpdate()`, `tomSelectReset()`) to set or clear dropdowns. Do not dispatch the raw `tom_select_*` browser events by hand.
- Each dropdown must have the `tom-select` npm package installed, and the package's `resources/js/wire-tomselect-all.js` loaded before Livewire starts.
