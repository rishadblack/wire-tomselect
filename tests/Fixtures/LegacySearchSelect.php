<?php

namespace Rishadblack\WireTomselect\Tests\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use Rishadblack\WireTomselect\SearchComponent;

/**
 * Overrides search() the 1.x way, without a return type, and records the term it receives.
 */
class LegacySearchSelect extends SearchComponent
{
    public static ?string $receivedSearch = null;

    public function builder(): Builder
    {
        return User::query();
    }

    public function configure(): void
    {
        $this->isSearchable();
    }

    /**
     * @return Builder
     */
    public function search(Builder $query, string $search)
    {
        static::$receivedSearch = $search;

        return $query->where('email', 'like', "{$search}%");
    }
}
