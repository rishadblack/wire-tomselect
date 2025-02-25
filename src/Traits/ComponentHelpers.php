<?php
namespace Rishadblack\WireTomselect\Traits;

trait ComponentHelpers
{
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
}