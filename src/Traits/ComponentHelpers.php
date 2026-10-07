<?php

namespace Rishadblack\WireTomselect\Traits;

use Livewire\Attributes\Locked;

trait ComponentHelpers
{
    /*
     * Untyped so 1.x subclasses can redeclare them (public $search_field = [...];).
     */

    /**
     * Column used as the option value. May be table-prefixed ("users.id").
     *
     * @var string
     */
    #[Locked]
    public $value_field = 'id';

    /**
     * Column used as the option label and the default ORDER BY. May be table-prefixed.
     *
     * @var string
     */
    #[Locked]
    public $label_field = 'name';

    /**
     * Columns matched with LIKE while searching.
     *
     * @var array<int, string>
     */
    #[Locked]
    public $search_field = ['name'];

    public function getValueField(bool $onlyName = false): string
    {
        return $onlyName ? $this->returnNameOnly($this->value_field) : (string) $this->value_field;
    }

    public function setValueField(string $name): self
    {
        $this->value_field = $name;

        return $this;
    }

    public function getLabelField(bool $onlyName = false): string
    {
        return $onlyName ? $this->returnNameOnly($this->label_field) : (string) $this->label_field;
    }

    public function setLabelField(string $name): self
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
            ? array_map(fn (string $field): string => $this->returnNameOnly($field), (array) $this->search_field)
            : (array) $this->search_field;
    }

    /**
     * @param  array<int, string>  $fields
     */
    public function setSearchField(array $fields = []): self
    {
        $this->search_field = array_values($fields);

        return $this;
    }

    public function showRemoveButton(): self
    {
        $this->is_remove_button = true;

        return $this;
    }

    /**
     * Strip a table prefix: "users.name" becomes "name".
     * Untyped, so 1.x overrides without types stay compatible.
     *
     * @param  string  $value
     * @return string
     */
    public function returnNameOnly($value)
    {
        $value = (string) $value;

        return str_contains($value, '.') ? substr($value, strrpos($value, '.') + 1) : $value;
    }
}
