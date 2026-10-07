<?php

namespace Rishadblack\WireTomselect\Tests\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use Rishadblack\WireTomselect\SearchComponent;

/**
 * Brings its own ordering and limit, which the package must leave alone.
 */
class RankedSelect extends SearchComponent
{
    public function builder(): Builder
    {
        return User::query()->orderByDesc('name')->limit(2);
    }

    public function configure(): void
    {
        $this->isSearchable();
        $this->setMaxOptions(50);
    }
}
