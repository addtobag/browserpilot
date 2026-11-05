<?php

namespace BrowserPilot\Facades;

use BrowserPilot\PilotManager;
use BrowserPilot\Testing\PilotFake;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \BrowserPilot\PendingSession session(?string $driver = null)
 * @method static \BrowserPilot\Session start(?string $driver = null)
 * @method static \BrowserPilot\Session|null find(string $id)
 * @method static \BrowserPilot\Session findOrFail(string $id)
 * @method static list<object> tools(?\BrowserPilot\Session $session = null)
 * @method static \BrowserPilot\Ai\Tools\AskHuman handoff(?\BrowserPilot\Session $session = null)
 * @method static \BrowserPilot\PilotManager using(\BrowserPilot\Session $session)
 * @method static \BrowserPilot\Session current()
 * @method static void releaseCurrent()
 * @method static \BrowserPilot\Contracts\Driver driver(?string $driver = null)
 * @method static \BrowserPilot\PilotManager extend(string $driver, \Closure $callback)
 *
 * @see PilotManager
 */
class Pilot extends Facade
{
    /**
     * Swap in a fake that never opens a real browser.
     *
     * @param  array<string, string>  $pages  URL pattern => page text
     */
    public static function fake(array $pages = []): PilotFake
    {
        $fake = new PilotFake(static::getFacadeApplication(), $pages);

        static::swap($fake);

        return $fake;
    }

    protected static function getFacadeAccessor(): string
    {
        return PilotManager::class;
    }
}
