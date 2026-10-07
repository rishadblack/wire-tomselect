<?php

namespace Rishadblack\WireTomselect\Traits;

use Livewire\Attributes\Locked;

trait ComponentHelpers
{
    /** Column used as the option value. May be table-prefixed ("users.id"). */
    #[Locked]
    public string $value_field = 'id';

    /** Column used as the option label and the default ORDER BY. May be table-prefixed. */
    #[Locked]
    public string $label_field = 'name';

    /**
     * Columns matched with LIKE while searching.
     *
     * @var array<int, string>
     */
    #[Locked]
    public array $search_field = ['name'];

    public function getValueField(bool $onlyName = false): string
    {
        return $onlyName ? $this->returnNameOnly($this->value_field) : $this->value_field;
    }

    public function setValueField(string $name): static
    {
        $this->value_field = $name;

        return $this;
    }

    public function getLabelField(bool $onlyName = false): string
    {
        return $onlyName ? $this->returnNameOnly($this->label_field) : $this->label_field;
    }

    public function setLabelField(string $name): static
    {
        $this->label_field = $name;

        return $this;
    }

    /**
     * @return array<int, string>
     */
    public function getSearchField(bool $onlyName = false): array
    {
        return $onlyName
            ? array_map(fn (string $field): string => $this->returnNameOnly($field), $this->search_field)
            : $this->search_field;
    }

    /**
     * @param  array<int, string>  $fields
     */
    public function setSearchField(array $fields): static
    {
        $this->search_field = array_values($fields);

        return $this;
    }

    public function showRemoveButton(): static
    {
        $this->is_remove_button = true;

        return $this;
    }

    /**
     * Strip a table prefix: "users.name" becomes "name".
     */
    public function returnNameOnly(string $value): string
    {
        return str_contains($value, '.') ? substr($value, strrpos($value, '.') + 1) : $value;
    }
}
