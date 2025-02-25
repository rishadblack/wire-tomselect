<?php
namespace Rishadblack\WireTomselect;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Modelable;
use Livewire\Attributes\Reactive;
use Livewire\Component;
use ReflectionClass;
use Rishadblack\WireTomselect\Traits\ComponentHelpers;

/**
 * Abstract SearchComponent for use in Livewire components.
 * Provides a base structure for building searchable dropdowns with configurable fields.
 */
abstract class SearchComponent extends Component
{
    use ComponentHelpers;

    #[Modelable]
    public $value; // Bound model property for selected value

    public $data;                    // Holds the mapped data to be displayed
    public $name;                    // Component's name
    public $select_id;               // Unique ID for the select input
    public $label;                   // Label for the select field
    public $search_query;            // Current search query
    public $placeholder;             // Placeholder text for the select input
    public $disabled;                // Boolean to control input's disabled state
    public $searchable;              // Boolean to enable/disable searching
    public $max_options = 20;        // Maximum number of options to display
    public $multiple;                // Boolean for multiple selection
    public $value_field = 'id';      // The field to use as the value
    public $label_field = 'name';    // The field to use as the label
    public $search_field = ['name']; // Fields to use for search queries
    public $create_load_component;
    public $create_load = [];
    public $create_event;

    /**
     * Abstract method for building the query.
     * @return Builder
     */
    abstract public function builder(): Builder;

    /**
     * Abstract method for configuring the component.
     * Customize settings and properties here.
     */
    abstract public function configure(): void;

    /**
     * Maps a collection to an array with the specified value and label fields.
     * @param Collection $collection
     * @return array
     */
    public function map(Collection $collection): array
    {
        return $collection->map(function ($item) {
            return [
                $this->getValueField() => $item->{$this->getValueField()}, // Default value mapping
                $this->getLabelField() => $item->{$this->getLabelField()}, // Default label mapping
            ];
        })->toArray();
    }

    /**
     * Generate a unique select ID by replacing dots with underscores in the name.
     */
    public function baseSelectId(): void
    {
        $this->select_id = Str::replace('.', '_', $this->name);
    }

    /**
     * Enable searching for the component.
     */
    public function isSearchable(): void
    {
        $this->searchable = true;
    }

    /**
     * Set the maximum number of options to display.
     * Ensures at least one option is displayed.
     * @param int|null $max
     */
    public function setMaxOptions(int $max = null): void
    {
        $this->max_options = $max ?: $this->max_options; // Default to current value if null
    }

    /**
     * Return the base query builder from the subclass.
     * @return Builder
     */
    public function baseBuilder(): Builder
    {
        return $this->builder();
    }

    /**
     * Perform the base mapping and optionally filter with a search query.
     * @param string|null $search
     * @return array
     */
    public function baseMap(string $search = null, $loadId = null): array
    {
        $this->configure();

        $query = $this->searchable && ! empty($search) ? $this->search($this->baseBuilder(), $search) : $this->baseBuilder();

        // Prevent overriding the limit if it's already applied
        if (! $query->getQuery()->limit && $this->searchable) {
            $query->limit($this->max_options);
        }

        if ($loadId) {
            $query->where('id', $loadId);
        }

        $this->data = $this->map($query->get());

        return $this->data;
    }

    /**
     * Default search logic for filtering results.
     * Override this method in subclasses for custom behavior.
     * @param Builder $query
     * @param string $search
     * @return Builder
     */
    public function search(Builder $query, string $search)
    {
        return $query->where(function ($query) use ($search) {
            foreach ($this->getSearchField() as $field) {
                $query->orWhere($field, 'like', "%{$search}%");
            }
        });
    }

    /**
     * Perform a search and return the mapped results.
     * @param string|null $search
     * @return array
     */
    public function searchBuilder(string $search = null): array
    {
        $this->search_query = $search;
        return $this->baseMap($search);
    }

    /**
     * Component mount method for initialization.
     * Preloads data and generates the select ID.
     */
    public function mount()
    {
        $this->baseMap();      // Load the initial data
        $this->baseSelectId(); // Generate the select ID

        if ($this->create_load_component) {
            $this->create_load = explode(',', $this->create_load_component);
        } else {
            $this->create_load = [];
        }
    }

    /**
     * Render the component view.
     * @return \Illuminate\View\View
     */
    public function render()
    {
        $this->configure();

        return view('wire-tomselect::search', [
            'reactive_props' => $this->getReactiveProps(),
        ]);
    }

    public function getReactiveProps(): array
    {
        $data = collect((new ReflectionClass($this))->getProperties())
            ->filter(function ($prop) {
                // Check if the property has ONLY the #[Reactive] attribute
                foreach ($prop->getAttributes() as $attribute) {
                    if ($attribute->getName() === Reactive::class) {
                        return true; // Only include properties marked as #[Reactive]
                    }
                }
                return false;
            })
            ->map(fn($prop) => $prop->getName())
            ->values()
            ->toArray();

        return $data;
    }

    public function baseMapWithId($id): array
    {
        return $this->baseMap(null, $id);
    }

}