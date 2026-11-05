<?php

namespace BrowserPilot\Events;

use BrowserPilot\Session;
use Illuminate\Foundation\Events\Dispatchable;

class HandedBack
{
    use Dispatchable;

    public function __construct(public Session $session) {}
}
