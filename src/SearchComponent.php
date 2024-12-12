<?php

namespace Rishadblack\WireTomselect;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Modelable;
use Livewire\Component;

abstract class SearchComponent extends Component
{
    #[Modelable]
    public $value;

    public $data;
    public $name;
    public $select_id;
    public $label;
    public $search_query;
    public $placeholder;
    public $disabled;
    public $searchable;
    public $max_options = 20;
    public $multiple;
    public $value_field = 'id';
    public $label_field = 'name';
    public $search_field = ['name'];

    abstract public function builder(): Builder;
    abstract public function configure(): void;

    public function map(Collection $collection): array
    {
        return $collection->map(function ($item) {
            return [
                $this->getValueField() => $item->{$this->getValueField()}, // Default value mapping
                $this->getLabelField() => $item->{$this->getLabelField()}, // Default label mapping
            ];
        })->toArray();
    }

    public function render()
    {
        $this->configure();
        return view('wire-tomselect::search');
    }

    public function baseSelectId(): void
    {
        $this->select_id = Str::replace('.', '_', $this->name);
    }

    public function isSearchable(): void
    {
        $this->searchable = true;
    }

    public function setMaxOptions(int $max = null): void
    {
        $this->max_options = $max ?: $this->max_options; // Default to current value if null
        $this->max_options = max(1, $this->max_options); // Ensure the max options is at least 1
    }

    public function baseBuilder(): Builder
    {
        return $this->builder();
    }

    public function baseMap(string $search = null): array
    {
        $query = $this->searchable && !empty($search) ? $this->search($this->baseBuilder(), $search) : $this->baseBuilder();

        // Prevent overriding the limit if it's already applied
        // if (!$query->getQuery()->limit) {
        $query->limit($this->max_options);
        // }

        $this->data = $this->map($query->get());

        return $this->data;
    }

    public function search(Builder $query, string $search)
    {
        // Example search logic - override in subclass to customize.
        return $query->where(function ($query) use ($search) {
            foreach ($this->getSearchField() as $field) {
                $query->orWhere($field, 'like', "%{$search}%");
            }
        });
    }

    public function searchBuilder(string $search = null): array
    {
        $this->search_query = $search;
        return $this->baseMap($search);
    }

    public function getValueField(): string
    {
        return $this->value_field;
    }

    public function setValueField(string $name = null): self
    {
        $this->value_field = $name;
        return $this;
    }

    public function getLabelField(): string
    {
        return $this->label_field;
    }

    public function setLabelField(string $name = null): self
    {
        $this->label_field = $name;
        return $this;
    }

    public function getSearchField(): array
    {
        return $this->search_field;
    }

    public function setSearchField(array $fields = []): self
    {
        $this->search_field = $fields;
        return $this;
    }

    public function mount()
    {
        $this->baseMap(); // Load the initial data
        $this->baseSelectId(); // Generate the select ID
    }
}
