<?php
namespace Rishadblack\WireTomselect\Traits;

trait ComponentHelpers
{
    public $value_field  = 'id';     // The field to use as the value
    public $label_field  = 'name';   // The field to use as the label
    public $search_field = ['name']; // Fields to use for search queries

    /**
     * Get the value field name.
     * @return string
     */
    public function getValueField(bool $onlyName = false): string
    {
        if ($onlyName) {
            return $this->returnNameOnly($this->value_field);
        }

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
    public function getLabelField(bool $onlyName = false): string
    {
        if ($onlyName) {
            return $this->returnNameOnly($this->label_field);
        }

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
    public function getSearchField(bool $onlyName = false): array
    {
        if ($onlyName) {
            return array_map(function ($field) {
                return $this->returnNameOnly($field);
            }, $this->search_field);
        }

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
     * Set the fields to be used for searching.
     * @param array $fields
     * @return self
     */
    public function showRemoveButton(): self
    {
        $this->is_remove_button = true;
        return $this;
    }

    public function returnNameOnly($value)
    {
        if (strpos($value, '.') !== false) {
            $parts = explode('.', $value);
            $value = end($parts);
        }

        return $value;

    }
}
