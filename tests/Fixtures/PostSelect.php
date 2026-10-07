<?php

namespace Rishadblack\WireTomselect\Tests\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use Rishadblack\WireTomselect\SearchComponent;

/**
 * Joins a second table and uses table-prefixed field names.
 */
class PostSelect extends SearchComponent
{
    public function builder(): Builder
    {
        return Post::query()
            ->join('users', 'users.id', '=', 'posts.user_id')
            ->select('posts.*', 'users.name as author_name');
    }

    public function configure(): void
    {
        $this->isSearchable();
        $this->setValueField('posts.id');
        $this->setLabelField('posts.title');
        $this->setSearchField(['posts.title', 'users.name']);
    }
}
