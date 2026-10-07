<?php

namespace Rishadblack\WireTomselect;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\View\View;
use InvalidArgumentException;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Modelable;
use Livewire\Attributes\Reactive;
use Livewire\Attributes\Renderless;
use Livewire\Component;
use ReflectionClass;
use ReflectionProperty;
use Rishadblack\WireTomselect\Traits\ComponentHelpers;

use function Livewire\store;

/**
 * Base Livewire component for a Tom Select dropdown backed by an Eloquent query.
 *
 * Subclasses describe the query in builder() and the field configuration in
 * configure(). Everything else (initial load, remote search, preloading the
 * selected value, reacting to parent props) is handled here.
 */
abstract class SearchComponent extends Component
{
    use ComponentHelpers;

    /** Longest search term forwarded to the query. */
    public const MAX_SEARCH_LENGTH = 255;

    /**
     * Cache of reactive property names per concrete class.
     *
     * @var array<class-string, array<int, string>>
     */
    protected static array $reactivePropertyCache = [];

    /*
     * The properties below are untyped on purpose. 1.x subclasses commonly redeclare
     * them (public $max_options = 50;), and PHP forbids an untyped redeclaration of a
     * typed property. The types are documented in the docblocks instead.
     */

    /**
     * The selected value. Bound to the parent with wire:model.
     *
     * @var mixed
     */
    #[Modelable]
    public $value;

    /**
     * The options currently loaded for the dropdown. Not locked: the 1.x inline
     * script (in published views) writes it back, and nothing on the server trusts it.
     *
     * @var array<int, array{id: mixed, name: mixed}>|null
     */
    public $data = [];

    /**
     * Unique field name. Defaults to the parent's wire:model property. Builds the DOM id and targets events.
     *
     * @var string|null
     */
    #[Locked]
    public $name;

    /**
     * DOM-safe id derived from the name.
     *
     * @var string|null
     */
    #[Locked]
    public $select_id;

    /** @var string|null */
    #[Locked]
    public $label;

    /** @var string|null */
    #[Locked]
    public $label_class;

    /** @var string|null */
    #[Locked]
    public $class;

    /** @var string|null */
    #[Locked]
    public $placeholder;

    /** @var bool|string */
    #[Locked]
    public $disabled = false;

    /** @var bool|string */
    #[Locked]
    public $multiple = false;

    /** @var bool */
    #[Locked]
    public $searchable = false;

    /** @var bool */
    #[Locked]
    public $is_remove_button = false;

    /**
     * Maximum number of options loaded per request. 0 means "use the config default".
     *
     * @var int|string
     */
    #[Locked]
    public $max_options = 0;

    /**
     * The term of the search being run. Readable from builder(), as in 1.x.
     *
     * @var string|null
     */
    #[Locked]
    public $search_query;

    /** Limit passed as a Blade attribute. Wins over setMaxOptions() for this instance. */
    #[Locked]
    public int $max_options_override = 0;

    /**
     * Event dispatched with the typed text when the user creates a new option.
     *
     * @var string|null
     */
    #[Locked]
    public $create_event;

    /**
     * "action,component" pair dispatched through the loadComponent event.
     *
     * @var string|null
     */
    #[Locked]
    public $create_load_component;

    /** @var array<int, string> */
    #[Locked]
    public $create_load = [];

    /** Fingerprint of the reactive props used to render the current options. */
    #[Locked]
    public ?string $reactive_signature = null;

    /**
     * The base Eloquent query for the dropdown.
     */
    abstract public function builder(): Builder;

    /**
     * Configure the fields, search behaviour and limits. Runs on every load.
     */
    abstract public function configure(): void;

    /**
     * Map the loaded models to options. Items must use the keys "id" and "name".
     *
     * @param  Collection<int, Model>  $collection
     * @return array<int, array{id: mixed, name: mixed}>
     */
    public function map(Collection $collection): array
    {
        $valueField = $this->getValueField(true);
        $labelField = $this->getLabelField(true);

        return $collection->map(fn (Model $item): array => [
            'id' => $item->{$valueField},
            'name' => $item->{$labelField},
        ])->values()->all();
    }

    /**
     * Render a Blade view to use as an option's "html" (or "item_html") key in map().
     *
     * @param  array<string, mixed>  $data
     */
    protected function optionHtml(string $view, array $data = []): string
    {
        return view($view, $data)->render();
    }

    /**
     * Replace the default LIKE search. Override for scopes, full-text search or relations.
     *
     * The return type is declared in the docblock only, so overrides written for 1.x
     * without ": Builder" stay compatible. Overrides may add ": Builder" freely.
     * The term arrives already trimmed to MAX_SEARCH_LENGTH characters.
     *
     * @return Builder
     */
    public function search(Builder $query, string $search)
    {
        return $query->where(function (Builder $query) use ($search): void {
            foreach ($this->getSearchField() as $field) {
                $query->orWhere($field, 'like', "%{$search}%");
            }
        });
    }

    /**
     * No return type, so 1.x overrides without one stay compatible.
     *
     * @return void
     */
    public function mount()
    {
        $this->disabled = $this->toBoolean($this->disabled);
        $this->multiple = $this->toBoolean($this->multiple);
        $this->max_options_override = max(0, (int) $this->max_options);
        $this->create_load = $this->create_load_component
            ? array_map('trim', explode(',', (string) $this->create_load_component))
            : [];
        $this->reactive_signature = $this->reactiveSignature();

        $this->loadSelectedOptions();
    }

    /**
     * No return type, so 1.x overrides without one stay compatible.
     *
     * @return View
     */
    public function render()
    {
        $this->resolveName();
        $this->applyConfiguration();

        $signature = $this->reactiveSignature();

        // Reload when a parent prop changed, or when the parent assigned a value
        // that is not among the loaded options. Both happen in the same request
        // as the parent's update, so the browser needs no extra round trip.
        if ($this->reactive_signature !== $signature || ! $this->hasOptionsForSelectedIds()) {
            $this->reactive_signature = $signature;
            $this->loadSelectedOptions();
        }

        return view('wire-tomselect::search', [
            'tomselect_config' => $this->tomselectConfig(),
            // Read by views published from 1.x.
            'reactive_props' => $this->getReactiveProps(),
        ]);
    }

    /**
     * Generate the DOM-safe select id from the name (1.x API).
     */
    public function baseSelectId(): void
    {
        $this->select_id = Str::replace('.', '_', (string) $this->name);
    }

    /**
     * Search and return the matching options. Called by Tom Select while the user types.
     *
     * @return array<int, array{id: mixed, name: mixed}>
     */
    #[Renderless]
    public function searchBuilder(?string $search = null): array
    {
        return $this->baseMap($search);
    }

    /**
     * Load the options, optionally filtered by a search term, making sure the given id is present.
     *
     * @return array<int, array{id: mixed, name: mixed}>
     */
    #[Renderless]
    public function baseMap(?string $search = null, mixed $loadId = null): array
    {
        return $this->data = $this->loadOptions($search, $loadId === null ? [] : (array) $loadId);
    }

    /**
     * Load the options and make sure the given id is present even when outside the limit.
     *
     * @return array<int, array{id: mixed, name: mixed}>
     */
    #[Renderless]
    public function baseMapWithId(mixed $id): array
    {
        return $this->baseMap(null, $id);
    }

    /**
     * Load the options and make sure every given id is present. One query for all ids.
     *
     * @param  array<int, mixed>  $ids
     * @return array<int, array{id: mixed, name: mixed}>
     */
    #[Renderless]
    public function baseMapWithIds(array $ids): array
    {
        return $this->baseMap(null, $ids);
    }

    public function isSearchable(): void
    {
        $this->searchable = true;
    }

    public function setMaxOptions(?int $max = null): void
    {
        if ($max !== null && $max > 0) {
            $this->max_options = $max;
        }
    }

    public function baseBuilder(): Builder
    {
        return $this->builder();
    }

    /**
     * Names of the properties marked #[Reactive] on the concrete class.
     *
     * @return array<int, string>
     */
    public function getReactiveProps(): array
    {
        return static::$reactivePropertyCache[static::class] ??= collect((new ReflectionClass($this))->getProperties(ReflectionProperty::IS_PUBLIC))
            ->filter(fn (ReflectionProperty $property): bool => $property->getAttributes(Reactive::class) !== [])
            ->map(fn (ReflectionProperty $property): string => $property->getName())
            ->values()
            ->all();
    }

    /**
     * Run the query and map the results.
     *
     * @param  array<int, mixed>  $ids  Values that must be included even when outside the limit.
     * @return array<int, array{id: mixed, name: mixed}>
     */
    protected function loadOptions(?string $search = null, array $ids = []): array
    {
        $this->search_query = filled($search) ? mb_substr($search, 0, static::MAX_SEARCH_LENGTH) : null;

        $this->applyConfiguration();

        $query = $this->baseBuilder();

        if ($this->searchable && filled($search)) {
            $query = $this->search($query, $this->search_query);
        }

        if (! $query->getQuery()->orders) {
            $query->orderBy($this->getLabelField());
        }

        if (! $query->getQuery()->limit) {
            $query->limit((int) $this->max_options);
        }

        $results = $query->get();

        $ids = $this->sanitizeIds($ids);

        if ($ids !== []) {
            $valueField = $this->getValueField(true);
            $loaded = $results->map(fn (Model $item): string => (string) $item->{$valueField})->all();
            $missing = array_values(array_filter($ids, fn (mixed $id): bool => ! in_array((string) $id, $loaded, true)));

            if ($missing !== []) {
                $results = $results->concat(
                    $this->baseBuilder()->whereIn($this->getValueField(), $missing)->get()
                );
            }
        }

        return $this->map($results);
    }

    /**
     * Settings handed to the Alpine component in the view.
     *
     * @return array<string, mixed>
     */
    protected function tomselectConfig(): array
    {
        return [
            'name' => $this->name,
            'multiple' => (bool) $this->multiple,
            'searchable' => (bool) $this->searchable,
            'removeButton' => (bool) $this->is_remove_button || (bool) $this->multiple,
            'createEvent' => $this->create_event,
            'createLoad' => $this->create_load,
            'loadThrottle' => (int) config('wire-tomselect.load_throttle', 300),
            'minSearchLength' => (int) config('wire-tomselect.min_search_length', 1),
            'reactiveProps' => $this->getReactiveProps(),
        ];
    }

    /**
     * Fall back to the parent's wire:model property when no name attribute was given.
     */
    protected function resolveName(): void
    {
        if (blank($this->name)) {
            $bindings = store($this)->get('bindings', []);
            $this->name = (string) (array_keys($bindings)[0] ?? '');
        }

        if (blank($this->name)) {
            throw new InvalidArgumentException(sprintf('[%s] requires a unique "name" attribute or a wire:model binding.', static::class));
        }

        if (blank($this->select_id)) {
            $this->baseSelectId();
        }
    }

    /**
     * Load the default options plus the selected ones, through the 1.x entry points
     * so that subclasses overriding baseMap() or baseMapWithId() keep working.
     */
    protected function loadSelectedOptions(): void
    {
        $ids = $this->selectedIds();

        if ($ids === []) {
            $this->baseMap();
        } elseif (is_array($this->value)) {
            $this->baseMapWithIds($ids);
        } else {
            $this->baseMapWithId($ids[0]);
        }
    }

    /**
     * Whether every selected id is present in the loaded options.
     */
    protected function hasOptionsForSelectedIds(): bool
    {
        $ids = $this->selectedIds();

        if ($ids === []) {
            return true;
        }

        $loaded = [];

        foreach (is_array($this->data) ? $this->data : [] as $option) {
            if (is_array($option) && isset($option['id']) && is_scalar($option['id'])) {
                $loaded[] = (string) $option['id'];
            }
        }

        foreach ($ids as $id) {
            if (! in_array((string) $id, $loaded, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Run the subclass configuration, then apply the config default and ceiling to the option limit.
     */
    protected function applyConfiguration(): void
    {
        $this->configure();

        $default = max(1, (int) config('wire-tomselect.max_options', 20));
        $limit = max(1, (int) config('wire-tomselect.max_options_limit', 100));

        $requested = $this->max_options_override ?: ((int) $this->max_options ?: $default);

        $this->max_options = min($requested, $limit);
    }

    /**
     * The currently selected ids as a flat list.
     *
     * @return array<int, mixed>
     */
    protected function selectedIds(): array
    {
        return $this->sanitizeIds(is_array($this->value) ? $this->value : [$this->value]);
    }

    /**
     * Keep only scalar, non-empty ids, and never more than the option ceiling.
     * Ids come from the browser (the bound value or a baseMapWithIds() call).
     *
     * @param  array<mixed>  $ids
     * @return array<int, int|string|float>
     */
    protected function sanitizeIds(array $ids): array
    {
        $limit = max(1, (int) config('wire-tomselect.max_options_limit', 100));

        $ids = array_filter($ids, fn (mixed $id): bool => (is_int($id) || is_float($id) || is_string($id)) && $id !== '');

        return array_slice(array_values(array_unique($ids, SORT_REGULAR)), 0, $limit);
    }

    protected function reactiveSignature(): ?string
    {
        $props = $this->getReactiveProps();

        if ($props === []) {
            return null;
        }

        $values = [];

        foreach ($props as $prop) {
            $values[$prop] = $this->{$prop};
        }

        return md5(json_encode($values) ?: '');
    }

    protected function toBoolean(mixed $value): bool
    {
        return is_bool($value) ? $value : filter_var($value, FILTER_VALIDATE_BOOL);
    }
}
