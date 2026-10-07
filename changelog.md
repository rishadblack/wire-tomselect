# Changelog

All notable changes to `rishadblack/wire-tomselect` are documented in this file.

## 2.0.2 - 2026-10-08

### Changed

- Dependencies refreshed and tested on PHP 8.4 (Symfony 8.1 components). No package code changes.

## 2.0.1 - 2026-10-07

### Fixed

1.x code crashed or silently misbehaved on 2.0.0. All of it runs unchanged again:

- Subclasses that redeclare properties without a type (`public $max_options = 50;`) no longer hit "Type of ... must be ...". Public properties are untyped again, with types in docblocks.
- Overrides without return types of `search()`, `mount()`, `render()` and `returnNameOnly()` no longer cause "must be compatible" fatal errors.
- Overrides of `baseMap()` and `baseMapWithId()` are honoured again on mount, re-render and search.
- `$search_query` is available in `builder()` again, and `baseSelectId()` is back.
- Setter overrides declared with `: self`, and `setSearchField()` called without arguments, work again.
- Named-argument calls such as `tomSelectUpdate(options: [...])` work again.
- Views published from 1.x render again: `$reactive_props` is passed to the view, `window.TomSelect` and `window.tom_select_set_value` are set, and `data` is no longer locked, since the 1.x inline script writes it back.
- The per-field browser events `{select_id}_set_value`, `_set_option` and `_set_reset` work again. They are deprecated.
- `max_options` passed as a string from Blade is cast to an integer.

### Security

- The 255-character cap on search terms now also applies to `search()` overrides.

## 2.0.0 - 2026-10-07

### Security

- Every configuration property on `SearchComponent` (`max_options`, `searchable`, `value_field`, `label_field`, `search_field`, `name`, `multiple`, `create_*`, ...) is now `#[Locked]`. The browser can no longer change which columns are searched, which column is used as the label, or how many rows are returned.
- A hard option ceiling (`wire-tomselect.max_options_limit`, default 100) is applied to every request, whatever the component or Blade attribute asks for.
- The option limit is now always applied, including to non-searchable dropdowns. Tom Select never rendered more than `max_options` anyway, so the extra rows were loaded for nothing.

### Changed

- Ids received from the browser (the bound value, `baseMapWithIds()`) are reduced to scalars, de-duplicated and capped at `max_options_limit`; search terms are cut at 255 characters.
- The browser ignores the response of a superseded value lookup, so rapid value changes cannot apply a stale selection.
- Added a GitHub Actions matrix (PHP 8.3 and 8.4, Laravel 11 to 13, Livewire 3 and 4).
- All glue JavaScript moved out of the Blade view into an Alpine component (`wireTomselect`) shipped in `resources/js/wire-tomselect-all.js`. Listeners are bound with `x-on` and cleaned up through Alpine's `destroy()`, which removes the per-instance `Livewire.hook` registrations that leaked under `wire:navigate`.
- `tomSelectUpdate()` and `tomSelectReset()` dispatch their payload as the named `fields` parameter (`detail.fields` in the browser). Test with `->assertDispatched('tom_select_set_value', fields: [...])`.
- The "create new option" flow stores the source dropdown on the component (`tom_select_field`) instead of in the session, so it works with several tabs open.
- Search methods (`searchBuilder`, `baseMap`, `baseMapWithId`, `baseMapWithIds`) are `#[Renderless]`, saving a view render per keystroke.
- When a `#[Reactive]` prop changes, the options are reloaded during the same re-render. The browser no longer makes a second request.
- Multiple selects load every missing selected value with a single `baseMapWithIds()` call instead of one request per value.
- `data` is a typed, locked array; `search_query` was removed. All properties are typed.
- Publish tags renamed to `wire-tomselect-config` and `wire-tomselect-views`.
- The disabled state no longer injects an inline red background.
- `composer.json` no longer pins a `version`; releases come from git tags.

### Added

- Rich option markup: `map()` items may carry `html` and `item_html` keys, rendered server-side (helper: `optionHtml()`), which the dropdown inserts instead of the escaped `name`.
- In searchable mode the browser no longer re-filters server results by `name`, so columns outside the label (such as email) are searchable. The default list is restored when the search text is cleared or the dropdown closes.

- `name` is optional when the dropdown is bound with `wire:model`; it defaults to the bound property.
- Assigning the bound property from a parent now selects the value without `tomSelectUpdate()`. The dropdown reloads its options in the same request when the value is not loaded yet, and the browser falls back to one `baseMapWithIds()` call only if needed.
- `config/wire-tomselect.php` with `max_options`, `max_options_limit`, `load_throttle`, and `min_search_length`.
- The `max_options` Blade attribute now wins over `setMaxOptions()` in `configure()`, so one instance can be limited differently from the rest.
- `baseMapWithIds(array $ids)`.
- Package test suite covering the query rules, locked properties, the option ceiling, the Blade output and the `WithTomselect` trait.

### Removed

- The empty `WireTomselect` class, its facade, and the `wire-tomselect` container binding.
- The undocumented per-field browser events (`{select_id}_set_option`, `{select_id}_set_value`, `{select_id}_set_reset`). Use `tomSelectUpdate()` and `tomSelectReset()`.

## 1.1.8

- Last release of the 1.x line.
