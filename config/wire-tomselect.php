<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default option limit
    |--------------------------------------------------------------------------
    |
    | Number of options loaded per request when a component does not call
    | setMaxOptions() and no max_options attribute is passed in Blade.
    |
    */

    'max_options' => 20,

    /*
    |--------------------------------------------------------------------------
    | Option ceiling
    |--------------------------------------------------------------------------
    |
    | Hard upper bound applied to every dropdown, whatever the component or the
    | Blade attribute asks for. Keeps a single request from loading a table.
    |
    */

    'max_options_limit' => 100,

    /*
    |--------------------------------------------------------------------------
    | Search behaviour
    |--------------------------------------------------------------------------
    |
    | load_throttle is the number of milliseconds Tom Select waits after the
    | last keystroke before searching. min_search_length is the number of
    | characters required before a search request is sent.
    |
    */

    'load_throttle' => 300,

    'min_search_length' => 1,

];
