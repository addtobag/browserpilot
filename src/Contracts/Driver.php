<?php

namespace BrowserPilot\Contracts;

use BrowserPilot\Session;
use BrowserPilot\SessionOptions;
use BrowserPilot\Support\RemoteSession;

interface Driver
{
    /**
     * Start a remote browser and return how to reach it.
     */
    public function start(SessionOptions $options): RemoteSession;

    /**
     * Stop the browser so it stops costing money.
     */
    public function release(Session $session): void;

    /**
     * A URL a person can open (usually in an iframe) to watch and control the browser.
     */
    public function liveViewUrl(Session $session): ?string;

    /**
     * Connect to the browser and return its active page.
     */
    public function page(Session $session): Page;
}
