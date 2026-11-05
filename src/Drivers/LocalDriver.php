<?php

namespace BrowserPilot\Drivers;

use BrowserPilot\Session;
use BrowserPilot\SessionOptions;
use BrowserPilot\Support\RemoteSession;
use HeadlessChromium\BrowserFactory;
use Illuminate\Support\Str;
use Throwable;

/**
 * Starts Chrome or Chromium on this machine. Great for local development and CI.
 * There is no live view, so human take-over isn't available with this driver.
 */
class LocalDriver extends CdpDriver
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(protected array $config) {}

    public function start(SessionOptions $options): RemoteSession
    {
        $factory = new BrowserFactory($this->config['binary'] ?? null);

        $browser = $factory->createBrowser(array_filter([
            'headless' => (bool) ($this->config['headless'] ?? true),
            'keepAlive' => true,
            'noSandbox' => true,
            'windowSize' => $options->viewport ?? ($this->config['window_size'] ?? [1280, 800]),
            'userDataDir' => $options->profile !== null
                ? storage_path('app/pilot/profiles/'.Str::slug($options->profile))
                : null,
        ], fn ($value) => $value !== null));

        return new RemoteSession(
            id: 'local-'.Str::lower((string) Str::ulid()),
            connectUrl: $browser->getSocketUri(),
            liveViewUrl: null,
            region: 'local',
        );
    }

    public function release(Session $session): void
    {
        try {
            BrowserFactory::connectToBrowser($session->connectUrl())->close();
        } catch (Throwable) {
            // Chrome has already gone.
        }
    }

    public function liveViewUrl(Session $session): ?string
    {
        return null;
    }
}
