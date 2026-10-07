<?php

namespace Rishadblack\WireTomselect\Tests\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use Rishadblack\WireTomselect\SearchComponent;

/**
 * Written the way 1.x apps wrote their dropdowns: untyped property redeclarations,
 * overrides without return types, reading $search_query and calling baseSelectId().
 */
class V1StyleSelect extends SearchComponent
{
    public $max_options = 7;

    public $placeholder = 'Pick a user';

    public $search_field = ['name', 'email'];

    public $value;

    public $data;

    /** @var array<int, string|null> */
    public static array $seenSearchQueries = [];

    public function mount()
    {
        parent::mount();
        $this->baseSelectId();
    }

    public function builder(): Builder
    {
        static::$seenSearchQueries[] = $this->search_query;

        return User::query();
    }

    public function configure(): void
    {
        $this->isSearchable();
    }

    public function render()
    {
        return parent::render();
    }

    public function returnNameOnly($value)
    {
        return parent::returnNameOnly($value);
    }

    public function setSearchField(array $fields = []): self
    {
        return parent::setSearchField($fields);
    }
}
