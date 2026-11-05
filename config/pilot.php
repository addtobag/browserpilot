<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default driver
    |--------------------------------------------------------------------------
    |
    | Where your browsers run. "pilot" uses Pilot Cloud, "browserbase" and
    | "steel" call those providers directly with your own keys, and "local"
    | starts Chrome on this machine.
    |
    */

    'default' => env('PILOT_DRIVER', 'pilot'),

    'drivers' => [

        'pilot' => [
            'url' => env('PILOT_URL', 'https://browserpilot.dev'),
            'key' => env('PILOT_API_KEY'),
            'region' => env('PILOT_REGION'),
        ],

        'browserbase' => [
            'key' => env('BROWSERBASE_API_KEY'),
            'project' => env('BROWSERBASE_PROJECT_ID'),
            'region' => env('BROWSERBASE_REGION', 'us-west-2'),
            'url' => env('BROWSERBASE_URL', 'https://api.browserbase.com'),
        ],

        'steel' => [
            'key' => env('STEEL_API_KEY'),
            'url' => env('STEEL_URL', 'https://api.steel.dev'),
        ],

        'local' => [
            'binary' => env('CHROME_PATH'),
            'headless' => (bool) env('PILOT_HEADLESS', true),
            'window_size' => [1280, 800],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Sessions
    |--------------------------------------------------------------------------
    |
    | "timeout" is how long (in seconds) a remote browser may live. Sessions
    | are recorded in the pilot_sessions table so a live view and a waiting
    | agent can share state across requests and queue workers.
    |
    */

    'session' => [
        'timeout' => (int) env('PILOT_SESSION_TIMEOUT', 900),
        'viewport' => [1280, 800],
    ],

    /*
    |--------------------------------------------------------------------------
    | Human take-over
    |--------------------------------------------------------------------------
    |
    | When an agent calls the handoff tool it waits (polling every "poll"
    | seconds) until a person hands control back, or "timeout" seconds pass.
    | Run agents that use handoff on a queue, not inside a web request.
    |
    */

    'handoff' => [
        'timeout' => (int) env('PILOT_HANDOFF_TIMEOUT', 600),
        'poll' => 2,
    ],

    /*
    |--------------------------------------------------------------------------
    | Agent tools
    |--------------------------------------------------------------------------
    */

    'tools' => [
        'max_text' => 6000,
        'max_elements' => 120,
    ],

    /*
    |--------------------------------------------------------------------------
    | Live view
    |--------------------------------------------------------------------------
    |
    | The gate ability checked before someone can see a live view or hand a
    | session back. Leave null to allow anyone who can reach the component.
    |
    */

    'live_view' => [
        'gate' => null,
        'poll' => '3s',
    ],

];
