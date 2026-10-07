<?php

namespace Rishadblack\WireTomselect\Tests\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Reactive;
use Rishadblack\WireTomselect\SearchComponent;

/**
 * Dependent dropdown filtered by a reactive prop from the parent.
 */
class FilteredUserSelect extends SearchComponent
{
    #[Reactive]
    public ?int $country_id = null;

    public function builder(): Builder
    {
        return User::query()->when($this->country_id, fn (Builder $query, int $countryId) => $query->where('country_id', $countryId));
    }

    public function configure(): void
    {
        $this->isSearchable();
    }
}
