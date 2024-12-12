<?php

namespace Rishadblack\WireTomselect;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Modelable;
use Livewire\Component;

/**
 * Abstract SearchComponent for use in Livewire components.
 * Provides a base structure for building searchable dropdowns with configurable fields.
 */
abstract class SearchComponent extends Component
{
    #[Modelable]
    public $value; // Bound model property for selected value

    public $data; // Holds the mapped data to be displayed
    public $name; // Component's name
    public $select_id; // Unique ID for the select input
    public $label; // Label for the select field
    public $search_query; // Current search query
    public $placeholder; // Placeholder text for the select input
    public $disabled; // Boolean to control input's disabled state
    public $searchable; // Boolean to enable/disable searching
    public $max_options = 20; // Maximum number of options to display
    public $multiple; // Boolean for multiple selection
    public $value_field = 'id'; // The field to use as the value
    public $label_field = 'name'; // The field to use as the label
    public $search_field = ['name']; // Fields to use for search queries

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
     * Render the component view.
     * @return \Illuminate\View\View
     */
    public function render()
    {
        $this->configure();
        return view('wire-tomselect::search');
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
        $this->max_options = max(1, $this->max_options); // Ensure the max options is at least 1
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
    public function baseMap(string $search = null): array
    {
        $query = $this->searchable && !empty($search) ? $this->search($this->baseBuilder(), $search) : $this->baseBuilder();

        // Prevent overriding the limit if it's already applied
        if (!$query->getQuery()->limit) {
            $query->limit($this->max_options);
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
     * Get the value field name.
     * @return string
     */
    public function getValueField(): string
    {
        return $this->value_field;
    }

    /**
     * Set the value field name.
     * @param string|null $name
     * @return self
     */
    public function setValueField(string $name = null): self
    {
        $this->value_field = $name;
        return $this;
    }

    /**
     * Get the label field name.
     * @return string
     */
    public function getLabelField(): string
    {
        return $this->label_field;
    }

    /**
     * Set the label field name.
     * @param string|null $name
     * @return self
     */
    public function setLabelField(string $name = null): self
    {
        $this->label_field = $name;
        return $this;
    }

    /**
     * Get the fields used for searching.
     * @return array
     */
    public function getSearchField(): array
    {
        return $this->search_field;
    }

    /**
     * Set the fields to be used for searching.
     * @param array $fields
     * @return self
     */
    public function setSearchField(array $fields = []): self
    {
        $this->search_field = $fields;
        return $this;
    }

    /**
     * Component mount method for initialization.
     * Preloads data and generates the select ID.
     */
    public function mount()
    {
        $this->baseMap(); // Load the initial data
        $this->baseSelectId(); // Generate the select ID
    }
}
