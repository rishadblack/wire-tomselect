<?php

namespace Rishadblack\WireTomselect\Tests\Fixtures;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use Rishadblack\WireTomselect\Traits\WithTomselect;

class OrderForm extends Component
{
    use WithTomselect;

    public string $name = '';

    public ?string $received_text = null;

    public function tomSelectText(string $text): void
    {
        $this->received_text = $text;
    }

    public function render(): View
    {
        return view('wire-tomselect::test-blank');
    }
}
