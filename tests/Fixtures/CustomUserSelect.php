<?php

namespace Rishadblack\WireTomselect\Tests\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Rishadblack\WireTomselect\SearchComponent;

/**
 * Overrides map() and search(): labels include the email, search matches the start of the name only.
 */
class CustomUserSelect extends SearchComponent
{
    public function builder(): Builder
    {
        return User::query();
    }

    public function configure(): void
    {
        $this->isSearchable();
    }

    public function map(Collection $collection): array
    {
        return $collection->map(fn (User $user): array => [
            'id' => $user->id,
            'name' => "{$user->name} <{$user->email}>",
        ])->all();
    }

    public function search(Builder $query, string $search): Builder
    {
        return $query->where('name', 'like', "{$search}%");
    }
}
