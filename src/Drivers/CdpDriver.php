<?php

namespace BrowserPilot\Drivers;

use BrowserPilot\Cdp\CdpPage;
use BrowserPilot\Contracts\Driver;
use BrowserPilot\Contracts\Page;
use BrowserPilot\Session;

abstract class CdpDriver implements Driver
{
    public function page(Session $session): Page
    {
        return CdpPage::connect($session->connectUrl());
    }
}
