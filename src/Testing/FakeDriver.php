<?php

namespace BrowserPilot\Testing;

use BrowserPilot\Contracts\Driver;
use BrowserPilot\Contracts\Page;
use BrowserPilot\Session;
use BrowserPilot\SessionOptions;
use BrowserPilot\Support\RemoteSession;
use Illuminate\Support\Str;

class FakeDriver implements Driver
{
    /**
     * @var array<string, FakePage>
     */
    protected array $pages = [];

    public function __construct(protected PilotFake $fake) {}

    public function start(SessionOptions $options): RemoteSession
    {
        $id = 'fake-'.Str::lower((string) Str::ulid());
        $this->fake->record('started', $options);

        return new RemoteSession($id, 'ws://fake/'.$id, 'https://live.fake/'.$id, $options->region);
    }

    public function release(Session $session): void
    {
        $this->fake->record('released', $session->id());
    }

    public function liveViewUrl(Session $session): ?string
    {
        return 'https://live.fake/'.$session->remoteId();
    }

    public function page(Session $session): Page
    {
        return $this->pages[$session->id()] ??= new FakePage($this->fake, $this->fake->pages());
    }
}
