<?php

namespace Rishadblack\WireTomselect\Tests\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use Rishadblack\WireTomselect\SearchComponent;

/**
 * Not searchable, uses the email as the option value and a small limit.
 */
class EmailValueSelect extends SearchComponent
{
    public function builder(): Builder
    {
        return User::query();
    }

    public function configure(): void
    {
        $this->setValueField('email');
        $this->setMaxOptions(3);
    }
}
