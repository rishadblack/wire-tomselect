<?php

namespace Rishadblack\WireTomselect\Tests\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use Rishadblack\WireTomselect\SearchComponent;

class UserSelect extends SearchComponent
{
    public function builder(): Builder
    {
        return User::query();
    }

    public function configure(): void
    {
        $this->isSearchable();
        $this->setSearchField(['name', 'email']);
        $this->setMaxOptions(5);
    }
}
