<?php

namespace Rishadblack\WireTomselect\Traits;

trait ComponentHelpers
{
    protected $value_field;
    protected $label_field;
    protected $search_field;

    public function setValueField(string $value_field)
    {
        $this->value_field = $value_field;
        return $this;
    }

    public function getValueField(): string
    {
        return $this->value_field ?? 'id';
    }

    public function setLabelField(string $label_field)
    {
        $this->label_field = $label_field;
        return $this;
    }

    public function getLabelField(): string
    {
        return $this->label_field ?? 'name';
    }

    public function setSearchField(array $search_field)
    {
        $this->search_field = $search_field;
        return $this;
    }

    public function getSearchField(): array
    {
        return count($this->search_field) > 0 ? $this->search_field : ['id', 'name'];
    }
}
