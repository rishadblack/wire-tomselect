<?php

namespace Rishadblack\WireTomselect\Tests\Fixtures;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class FilterPage extends Component
{
    public ?int $country_id = null;

    public ?int $user_id = null;

    public function render(): View
    {
        return view('wire-tomselect::filter-page');
    }
}
