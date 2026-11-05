<?php

namespace BrowserPilot\Events;

use BrowserPilot\Session;
use Illuminate\Foundation\Events\Dispatchable;

class SessionStarted
{
    use Dispatchable;

    public function __construct(public Session $session) {}
}
