<?php

namespace Rishadblack\WireTomselect\Tests\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use Rishadblack\WireTomselect\SearchComponent;

/**
 * Overrides baseMap() the 1.x way to append a fixed "Unassigned" option.
 */
class V1BaseMapSelect extends SearchComponent
{
    public function builder(): Builder
    {
        return User::query();
    }

    public function configure(): void
    {
        $this->isSearchable();
    }

    public function baseMap(?string $search = null, $loadId = null): array
    {
        $options = parent::baseMap($search, $loadId);
        $options[] = ['id' => 0, 'name' => 'Unassigned'];

        return $this->data = $options;
    }
}
