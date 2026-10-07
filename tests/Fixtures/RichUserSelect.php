<?php

namespace Rishadblack\WireTomselect\Tests\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Rishadblack\WireTomselect\SearchComponent;

/**
 * Plain label, rich HTML row rendered from a Blade view, searchable by email.
 */
class RichUserSelect extends SearchComponent
{
    public function builder(): Builder
    {
        return User::query();
    }

    public function configure(): void
    {
        $this->isSearchable();
        $this->setSearchField(['name', 'email']);
    }

    public function map(Collection $collection): array
    {
        return $collection->map(fn (User $user): array => [
            'id' => $user->id,
            'name' => $user->name,
            'html' => $this->optionHtml('wire-tomselect::user-option', ['user' => $user]),
        ])->all();
    }
}
