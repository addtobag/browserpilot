<?php

namespace BrowserPilot;

use BrowserPilot\Ai\Tools\AskHuman;
use BrowserPilot\Ai\Tools\Browse;
use BrowserPilot\Ai\Tools\Click;
use BrowserPilot\Ai\Tools\ReadPage;
use BrowserPilot\Ai\Tools\Screenshot;
use BrowserPilot\Ai\Tools\TypeText;
use BrowserPilot\Contracts\Driver;
use BrowserPilot\Contracts\ProfileStore;
use BrowserPilot\Contracts\SessionStore;
use BrowserPilot\Drivers\BrowserbaseDriver;
use BrowserPilot\Drivers\LocalDriver;
use BrowserPilot\Drivers\PilotDriver;
use BrowserPilot\Drivers\SteelDriver;
use BrowserPilot\Events\SessionStarted;
use Closure;
use Illuminate\Support\Manager;

/**
 * @method Driver driver(?string $driver = null)
 */
class PilotManager extends Manager
{
    /**
     * The session shared by Pilot::tools() and Pilot::handoff() when no session is passed.
     */
    protected ?Session $current = null;

    public function getDefaultDriver(): string
    {
        return (string) $this->config->get('pilot.default', 'pilot');
    }

    /**
     * Start building a browser session.
     */
    public function session(?string $driver = null): PendingSession
    {
        return new PendingSession($this, $driver);
    }

    /**
     * Start a session with default options.
     */
    public function start(?string $driver = null): Session
    {
        return $this->session($driver)->start();
    }

    public function startSession(SessionOptions $options, ?string $driver = null): Session
    {
        $driver ??= $this->getDefaultDriver();
        $options->timeout ??= (int) $this->config->get('pilot.session.timeout', 900);
        $options->viewport ??= $this->config->get('pilot.session.viewport', [1280, 800]);

        $remote = $this->driver($driver)->start($options);

        $attributes = $this->store()->create([
            'driver' => $driver,
            'remote_id' => $remote->id,
            'connect_url' => $remote->connectUrl,
            'live_view_url' => $remote->liveViewUrl,
            'status' => Session::RUNNING,
            'profile' => $options->profile,
            'region' => $remote->region ?? $options->region,
            'metadata' => $options->metadata ?: null,
            'owner_type' => $options->owner?->getMorphClass(),
            'owner_id' => $options->owner?->getKey(),
        ]);

        $session = new Session($this, $attributes);

        event(new SessionStarted($session));

        return $session;
    }

    /**
     * Find a session you started earlier (for example in a queued job or a Livewire component).
     */
    public function find(string $id): ?Session
    {
        $attributes = $this->store()->find($id);

        return $attributes === null ? null : new Session($this, $attributes);
    }

    public function findOrFail(string $id): Session
    {
        return $this->find($id) ?? throw new Exceptions\PilotException("Browser session [{$id}] was not found.");
    }

    /**
     * Agent tools: browse, click, type, read and screenshot.
     *
     * Pass a session to control a specific browser. Without one, the tools share a
     * browser that starts the first time the agent uses it and is released when the
     * request or queued job finishes.
     *
     * @return list<object>
     */
    public function tools(?Session $session = null): array
    {
        $resolver = $this->resolverFor($session);

        return [
            new Browse($resolver),
            new Click($resolver),
            new TypeText($resolver),
            new ReadPage($resolver),
            new Screenshot($resolver),
        ];
    }

    /**
     * A tool the agent calls to ask a person to take over, waiting until they hand back.
     */
    public function handoff(?Session $session = null): AskHuman
    {
        return new AskHuman($this->resolverFor($session));
    }

    /**
     * Use this session for tools created without one.
     */
    public function using(Session $session): static
    {
        $this->current = $session;

        return $this;
    }

    /**
     * The shared session, started on first use.
     */
    public function current(): Session
    {
        if ($this->current === null || $this->current->hasEnded()) {
            $this->current = $this->start();
        }

        return $this->current;
    }

    public function hasCurrent(): bool
    {
        return $this->current !== null && ! $this->current->hasEnded();
    }

    /**
     * Release the shared session, if one was started.
     */
    public function releaseCurrent(): void
    {
        $this->current?->release();
        $this->current = null;
    }

    public function store(): SessionStore
    {
        return $this->container->make(SessionStore::class);
    }

    public function profiles(): ProfileStore
    {
        return $this->container->make(ProfileStore::class);
    }

    /**
     * @return Closure(): Session
     */
    protected function resolverFor(?Session $session): Closure
    {
        return $session !== null
            ? fn () => $session
            : fn () => $this->current();
    }

    protected function createPilotDriver(): Driver
    {
        return new PilotDriver((array) $this->config->get('pilot.drivers.pilot', []));
    }

    protected function createBrowserbaseDriver(): Driver
    {
        return new BrowserbaseDriver((array) $this->config->get('pilot.drivers.browserbase', []), $this->profiles());
    }

    protected function createSteelDriver(): Driver
    {
        return new SteelDriver((array) $this->config->get('pilot.drivers.steel', []));
    }

    protected function createLocalDriver(): Driver
    {
        return new LocalDriver((array) $this->config->get('pilot.drivers.local', []));
    }
}
