<?php

namespace BrowserPilot;

use Illuminate\Database\Eloquent\Model;

/**
 * Fluent builder returned by Pilot::session().
 *
 *     $session = Pilot::session()->profile('acme-portal')->region('eu-central-1')->start();
 */
class PendingSession
{
    protected SessionOptions $options;

    public function __construct(protected PilotManager $manager, protected ?string $driver = null)
    {
        $this->options = new SessionOptions;
    }

    /**
     * Use a saved login. People sign in once and later sessions stay signed in.
     */
    public function profile(?string $name): static
    {
        $this->options->profile = $name;

        return $this;
    }

    public function region(?string $region): static
    {
        $this->options->region = $region;

        return $this;
    }

    /**
     * Maximum lifetime of the browser in seconds.
     */
    public function timeout(int $seconds): static
    {
        $this->options->timeout = $seconds;

        return $this;
    }

    public function keepAlive(bool $keepAlive = true): static
    {
        $this->options->keepAlive = $keepAlive;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function metadata(array $metadata): static
    {
        $this->options->metadata = array_merge($this->options->metadata, $metadata);

        return $this;
    }

    public function viewport(int $width, int $height): static
    {
        $this->options->viewport = [$width, $height];

        return $this;
    }

    /**
     * The model (usually a user or team) this browser belongs to.
     */
    public function for(?Model $owner): static
    {
        $this->options->owner = $owner;

        return $this;
    }

    public function options(): SessionOptions
    {
        return $this->options;
    }

    public function start(): Session
    {
        return $this->manager->startSession($this->options, $this->driver);
    }
}
