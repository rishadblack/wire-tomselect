<?php

namespace Rishadblack\WireTomselect\Facades;

use Illuminate\Support\Facades\Facade;

class WireTomselect extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'wire-tomselect';
    }
}
