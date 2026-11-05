<?php

namespace BrowserPilot\Events;

use BrowserPilot\Session;
use Illuminate\Foundation\Events\Dispatchable;

class SessionReleased
{
    use Dispatchable;

    public function __construct(public Session $session) {}
}
