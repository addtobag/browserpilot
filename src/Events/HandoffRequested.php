<?php

namespace BrowserPilot\Events;

use BrowserPilot\Session;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * An agent (or a person) needs someone to take over the browser.
 * Listen for this to send a push notification, email or Slack message
 * linking to your live view.
 */
class HandoffRequested
{
    use Dispatchable;

    public function __construct(public Session $session, public string $reason) {}
}
